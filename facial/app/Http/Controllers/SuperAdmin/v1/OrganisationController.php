<?php

namespace App\Http\Controllers\SuperAdmin\v1;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganisationController extends Controller
{
    private const STATUSES = ['active', 'inactive', 'suspended'];

    /**
     * Adds a `users_count` column using a subquery, so the Organisation
     * model does not need a users() relation.
     */
    private function withUsersCount(): Builder
    {
        return Organisation::query()
            ->select('organisations.*')
            ->selectSub(
                User::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('users.organisation_id', 'organisations.id'),
                'users_count'
            );
    }

    /**
     * GET admin/organisations
     */
    public function listOrganisation(Request $request): View
    {
        Gate::authorize('viewAny', Organisation::class);

        $filters = $request->validate([
            'q'      => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ]);

        $organisations = $this->withUsersCount()
            ->when(! empty($filters['status']), fn ($q) => $q->where('organisations.status', $filters['status']))
            ->when(! empty($filters['q']), function ($q) use ($filters) {
                $term = '%' . addcslashes($filters['q'], '%_\\') . '%';

                $q->where(fn ($w) => $w
                    ->where('organisations.name', 'like', $term)
                    ->orWhere('organisations.slug', 'like', $term));
            })
            ->orderBy('organisations.name')
            ->paginate(15)
            ->appends(array_filter($filters));

        return view('SuperAdmin.organisations.index', [
            'organisations' => $organisations,
            'filters'       => $filters,
            'statuses'      => self::STATUSES,
        ]);
    }

    /**
     * GET admin/organisations/{uuid}
     */
    public function getOrganisation(Request $request, string $uuid): View|RedirectResponse
    {
        // Opened directly in the browser? Show the list with this organisation's drawer open.
        if (! $request->ajax()) {
            return redirect()->route('superadmin.organisations.index', ['organisation' => $uuid]);
        }

        $organisation = $this->withUsersCount()
            ->where('organisations.uuid', $uuid)
            ->firstOrFail();

        Gate::authorize('view', $organisation);

        return view('SuperAdmin.organisations.drawer', [
            'organisation' => $organisation,
            'statuses'     => self::STATUSES,
        ]);
    }

    /**
     * PUT/PATCH admin/organisations/{uuid}
     * The slug is intentionally NOT editable.
     */
    public function updateOrganisation(Request $request, string $uuid): JsonResponse|RedirectResponse
    {
        $organisation = Organisation::query()->where('uuid', $uuid)->firstOrFail();

        Gate::authorize('update', $organisation);

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:150'],
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);

        // Stop a super admin from locking themselves out.
        if ($request->user()->organisation_id === $organisation->id && $data['status'] !== 'active') {
            return $this->fail($request, 'You cannot deactivate or suspend your own organisation.');
        }

        // Assigned directly because "status" is not in the model's $fillable.
        $organisation->name   = $data['name'];
        $organisation->status = $data['status'];
        $organisation->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Organisation updated.']);
        }

        return redirect()
            ->route('superadmin.organisations.index')
            ->with('success', 'Organisation updated.');
    }

    /**
     * DELETE admin/organisations/{uuid}
     * Refused while the organisation still has users; deactivate it instead.
     */
    public function deleteOrganisation(Request $request, string $uuid): JsonResponse|RedirectResponse
    {
        $organisation = Organisation::query()->where('uuid', $uuid)->firstOrFail();

        Gate::authorize('delete', $organisation);

        if ($request->user()->organisation_id === $organisation->id) {
            return $this->fail($request, 'You cannot delete your own organisation.');
        }

        if (User::query()->where('organisation_id', $organisation->id)->exists()) {
            return $this->fail($request, 'This organisation still has users. Remove or reassign them, or set it to inactive instead.');
        }

        try {
            $organisation->delete();
        } catch (QueryException $e) {
            return $this->fail($request, 'This organisation is still referenced by other records and cannot be deleted.');
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Organisation deleted.']);
        }

        return redirect()
            ->route('superadmin.organisations.index')
            ->with('success', 'Organisation deleted.');
    }

    private function fail(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->withErrors(['organisation' => $message]);
    }
}