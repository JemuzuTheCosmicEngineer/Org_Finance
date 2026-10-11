<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IcsController extends Controller
{
    private const CATEGORIES = ['Membership Fees', 'Events', 'Donations', 'Supplies', 'Operations', 'Other'];
    private const ROLES      = ['Admin', 'Treasurer', 'Viewer'];

    /* ---------- helpers ---------- */

    private function role(): string
    {
        return strtolower((string) (auth()->user()->role ?? 'viewer'));
    }

    private function isAdmin(): bool
    {
        return $this->role() === 'admin';
    }

    private function canWrite(): bool
    {
        return in_array($this->role(), ['admin', 'treasurer'], true);
    }

    private function log(string $action, string $module, string $details): void
    {
        ActivityLog::create([
            'user_name' => auth()->user()->name,
            'role'      => $this->role(),
            'action'    => $action,
            'module'    => $module,
            'details'   => Str::limit($details, 250, ''),
        ]);
    }

    private function deny(): JsonResponse
    {
        return response()->json(['message' => 'You do not have permission to do that.'], 403);
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 422);
    }

    /* ---------- everything the dashboard needs, in one request ---------- */

    public function data(): JsonResponse
    {
        $records = FinancialRecord::orderBy('id')->get();

        $users = $this->isAdmin()
            ? User::orderBy('id')->get(['id', 'name', 'email', 'role', 'status'])
            : [];

        $logs = ActivityLog::orderByDesc('id')->limit(500)->get()->map(function ($l) {
            return [
                'id'      => $l->id,
                'time'    => $l->created_at->copy()->timezone('Asia/Manila')->format('n/j/Y, g:i:s A'),
                'user'    => $l->user_name,
                'role'    => $l->role,
                'action'  => $l->action,
                'module'  => $l->module,
                'details' => $l->details,
            ];
        });

        return response()->json(['records' => $records, 'users' => $users, 'logs' => $logs]);
    }

    /* ---------- records ---------- */

    private function recordRules(): array
    {
        return [
            'title'    => ['required', 'string', 'min:3', 'max:60'],
            'type'     => ['required', Rule::in(['Income', 'Expense'])],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'amount'   => ['required', 'numeric', 'gt:0', 'max:10000000'],
            'date'     => ['required', 'date_format:Y-m-d', 'before_or_equal:' . now('Asia/Manila')->toDateString()],
            'status'   => ['required', Rule::in(['Pending', 'Approved'])],
        ];
    }

    public function storeRecord(Request $request): JsonResponse
    {
        if (!$this->canWrite()) return $this->deny();

        $data   = $request->validate($this->recordRules());
        $record = FinancialRecord::create($data);
        $record->update(['ref' => 'ICS-' . str_pad((string) $record->id, 4, '0', STR_PAD_LEFT)]);

        $this->log('CREATE', 'Information Management', "Created {$record->ref} - {$record->title}");

        return response()->json($record->fresh(), 201);
    }

    public function updateRecord(Request $request, FinancialRecord $record): JsonResponse
    {
        if (!$this->canWrite()) return $this->deny();

        $record->update($request->validate($this->recordRules()));

        $this->log('UPDATE', 'Information Management', "Updated {$record->ref} - {$record->title}");

        return response()->json($record->fresh());
    }

    public function destroyRecord(FinancialRecord $record): JsonResponse
    {
        if (!$this->canWrite()) return $this->deny();

        $this->log('DELETE', 'Information Management', "Deleted {$record->ref} - {$record->title}");
        $record->delete();

        return response()->json(['ok' => true]);
    }

    /* ---------- users (admin only) ---------- */

    private function userRules(?int $userId = null, bool $passwordRequired = true): array
    {
        return [
            'name'     => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z][A-Za-z .\'-]{2,49}$/'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role'     => ['required', Rule::in(self::ROLES)],
            'status'   => ['required', Rule::in(['Active', 'Inactive'])],
            'password' => array_merge(
                [$passwordRequired ? 'required' : 'nullable'],
                ['string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/\d/']
            ),
        ];
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if ($user->role !== 'Admin' || $user->status !== 'Active') return false;

        return User::where('id', '!=', $user->id)
            ->where('role', 'Admin')
            ->where('status', 'Active')
            ->count() === 0;
    }

    public function storeUser(Request $request): JsonResponse
    {
        if (!$this->isAdmin()) return $this->deny();

        $data = $request->validate($this->userRules());

        $user = new User();
        $user->forceFill([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'role'     => $data['role'],
            'status'   => $data['status'],
            'password' => Hash::make($data['password']),
        ])->save();

        $this->log('CREATE', 'User Management', 'Created user ' . $user->email);

        return response()->json($user->only(['id', 'name', 'email', 'role', 'status']), 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        if (!$this->isAdmin()) return $this->deny();

        $data = $request->validate($this->userRules($user->id, false));

        if ($this->isLastActiveAdmin($user) && ($data['role'] !== 'Admin' || $data['status'] !== 'Active')) {
            return $this->fail('At least one active Admin is required.');
        }

        $user->forceFill([
            'name'   => $data['name'],
            'email'  => $data['email'],
            'role'   => $data['role'],
            'status' => $data['status'],
        ]);
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $this->log('UPDATE', 'User Management', 'Updated user ' . $user->email);

        return response()->json($user->only(['id', 'name', 'email', 'role', 'status']));
    }

    public function destroyUser(User $user): JsonResponse
    {
        if (!$this->isAdmin()) return $this->deny();

        if ($user->id === auth()->id()) {
            return $this->fail('You cannot delete your own account.');
        }
        if ($this->isLastActiveAdmin($user)) {
            return $this->fail('Cannot delete the last active Admin.');
        }

        $this->log('DELETE', 'User Management', 'Deleted user ' . $user->email);
        $user->delete();

        return response()->json(['ok' => true]);
    }

    /* ---------- client-side events (login, filtered report views) ---------- */

    public function storeLog(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action'  => ['required', Rule::in(['LOGIN', 'VIEW'])],
            'module'  => ['required', 'string', 'max:60'],
            'details' => ['required', 'string', 'max:250'],
        ]);

        $this->log($data['action'], $data['module'], $data['details']);

        return response()->json(['ok' => true], 201);
    }
}