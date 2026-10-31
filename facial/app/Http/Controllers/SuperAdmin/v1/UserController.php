<?php

namespace App\Http\Controllers\SuperAdmin\v1;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\Roles;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function listUser(Request $request): View {
        Gate::authorize('viewAny', User::class);

        $actor        = $request->user();
        $isSuperAdmin = $actor->hasRole('super_admin');

        $statuses = array_column(UserStatus::cases(), 'value');

        $filters = $request->validate([
            'q'            => ['nullable', 'string', 'max:100'],
            'role'         => ['nullable', 'string', 'exists:roles,slug'],
            'status'       => ['nullable', Rule::in($statuses)],
            'organisation' => ['nullable', 'uuid', 'exists:organisations,uuid'],
        ]);

        $users = User::query()
            ->with(['role', 'stream', 'organisation'])
            ->when(! $isSuperAdmin, fn ($q) => $q->where('organisation_id', $actor->organisation_id))
            ->when($isSuperAdmin && ! empty($filters['organisation']), fn ($q) => $q
                ->whereHas('organisation', fn ($o) => $o->where('uuid', $filters['organisation'])))
            ->when(! empty($filters['role']), fn ($q) => $q
                ->whereHas('role', fn ($r) => $r->where('slug', $filters['role'])))
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['q']), function ($q) use ($filters) {
                $term = '%' . addcslashes($filters['q'], '%_\\') . '%';

                $q->where(fn ($w) => $w
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$term])
                    ->orWhere('email', 'like', $term)
                    ->orWhere('employee_id', 'like', $term));
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15)
            ->appends(array_filter($filters));

        return view('SuperAdmin.index', [
            'users'         => $users,
            'filters'       => $filters,
            'statuses'      => $statuses,
            'isSuperAdmin'  => $isSuperAdmin,
            'roles'         => Roles::query()
                ->when(! $isSuperAdmin, fn ($q) => $q->where('slug', '!=', 'super_admin'))
                ->orderBy('name')
                ->get(['slug', 'name']),
            'organisations' => $isSuperAdmin
                ? Organisation::query()->orderBy('name')->get(['uuid', 'name'])
                : collect(),
        ]);
    }

    /**
     * GET admin/v1/users/{uuid}
     * Single user detail. Cross-organisation access is rejected by UserPolicy::view.
     */
    public function getUser(Request $request, string $uuid): View|RedirectResponse
    {
        // Opened directly in the browser? Show the list with this user's drawer open.
        if (! $request->ajax()) {
            return redirect()->route('superadmin.users.index', ['user' => $uuid]);
        }

        $user = User::query()
            ->with(['role', 'stream', 'organisation', 'studentProfile'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        Gate::authorize('view', $user);

        return view('SuperAdmin.drawer', ['user' => $user]);
    }
}

