<?php

namespace App\Http\Controllers\SuperAdmin\v1;

use App\Enums\StreamStatus;
use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StreamController extends Controller
{
    public function listStream(Request $request): View
    {
        $actor        = $request->user();
        $isSuperAdmin = $actor->hasRole('super_admin');

        $statuses = array_column(StreamStatus::cases(), 'value');

        $filters = $request->validate([
            'q'            => ['nullable', 'string', 'max:100'],
            'status'       => ['nullable', Rule::in($statuses)],
            'organisation' => ['nullable', 'uuid', 'exists:organisations,uuid'],
        ]);

        $streams = $this->scoped($request)
            ->with('organisation:id,uuid,name')
            ->withCount(['users', 'students'])
            ->when($isSuperAdmin && ! empty($filters['organisation']), fn ($q) => $q
                ->whereHas('organisation', fn ($o) => $o->where('uuid', $filters['organisation'])))
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['q']), function ($q) use ($filters) {
                $term = '%' . addcslashes($filters['q'], '%_\\') . '%';

                $q->where(fn ($w) => $w
                    ->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(15)
            ->appends(array_filter($filters));

        return view('SuperAdmin.streams.index', [
            'streams'       => $streams,
            'filters'       => $filters,
            'statuses'      => $statuses,
            'isSuperAdmin'  => $isSuperAdmin,
            'organisations' => $isSuperAdmin
                ? Organisation::query()->orderBy('name')->get(['uuid', 'name'])
                : collect(),
        ]);
    }

    /**
     * GET admin/streams/{uuid}
     * Single stream detail. Other organisations' streams return 404 for non-super-admins.
     */
    public function getStream(Request $request, string $uuid): View|RedirectResponse
    {
        // Opened directly in the browser? Show the list with this stream's drawer open.
        if (! $request->ajax()) {
            return redirect()->route('superadmin.streams.index', ['stream' => $uuid]);
        }

        $stream = $this->scoped($request)
            ->with('organisation:id,uuid,name')
            ->withCount(['users', 'students'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('SuperAdmin.streams.drawer', [
            'stream'       => $stream,
            'statuses'     => array_column(StreamStatus::cases(), 'value'),
            'isSuperAdmin' => $request->user()->hasRole('super_admin'),
        ]);
    }

    /** POST admin/streams */
    public function createStream(Request $request): JsonResponse|RedirectResponse
    {
        $actor        = $request->user();
        $isSuperAdmin = $actor->hasRole('super_admin');

        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'organisation' => [Rule::requiredIf($isSuperAdmin), 'nullable', 'uuid', 'exists:organisations,uuid'],
            'code'         => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/'],
            'name'         => ['required', 'string', 'max:100'],
        ]);

        // Never trust an organisation from a non-super-admin; use their own.
        $organisationId = $isSuperAdmin
            ? Organisation::query()->where('uuid', $data['organisation'])->value('id')
            : $actor->organisation_id;

        if (Stream::query()->where('organisation_id', $organisationId)->where('code', $data['code'])->exists()) {
            return $this->fail($request, 'This organisation already has a stream with that code.');
        }

        $stream = Stream::create([
            'organisation_id' => $organisationId,
            'code'            => $data['code'],
            'name'            => $data['name'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stream created.', 'uuid' => $stream->uuid], 201);
        }

        return redirect()->route('superadmin.streams.index')->with('success', 'Stream created.');
    }

    /** PUT/PATCH admin/streams/{uuid}. The owning organisation is not editable. */
    public function updateStream(Request $request, string $uuid): JsonResponse|RedirectResponse
    {
        $stream = $this->scoped($request)->where('uuid', $uuid)->firstOrFail();

        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code'   => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/'],
            'name'   => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(array_column(StreamStatus::cases(), 'value'))],
        ]);

        $duplicate = Stream::query()
            ->where('organisation_id', $stream->organisation_id)
            ->where('code', $data['code'])
            ->where('id', '!=', $stream->id)
            ->exists();

        if ($duplicate) {
            return $this->fail($request, 'This organisation already has a stream with that code.');
        }

        // Assigned directly because "status" is not in the model's $fillable.
        $stream->code   = $data['code'];
        $stream->name   = $data['name'];
        $stream->status = $data['status'];
        $stream->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stream updated.']);
        }

        return redirect()->route('superadmin.streams.index')->with('success', 'Stream updated.');
    }

    /** DELETE admin/streams/{uuid}. Refused while users are assigned. */
    public function deleteStream(Request $request, string $uuid): JsonResponse|RedirectResponse
    {
        $stream = $this->scoped($request)->where('uuid', $uuid)->firstOrFail();

        if (User::query()->where('stream_id', $stream->id)->exists()) {
            return $this->fail($request, 'Users are still assigned to this stream. Reassign them first, or set it to inactive instead.');
        }

        try {
            $stream->delete();
        } catch (QueryException $e) {
            return $this->fail($request, 'This stream is still referenced by other records and cannot be deleted.');
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stream deleted.']);
        }

        return redirect()->route('superadmin.streams.index')->with('success', 'Stream deleted.');
    }

    /** Super admin reaches every stream; anyone else only their own organisation's. */
    private function scoped(Request $request): Builder
    {
        $actor = $request->user();

        return Stream::query()->when(
            ! $actor->hasRole('super_admin'),
            fn ($q) => $q->where('organisation_id', $actor->organisation_id)
        );
    }

    private function fail(Request $request, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->withErrors(['stream' => $message])->withInput();
    }
}