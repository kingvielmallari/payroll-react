<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        // Calculate user statistics by role (using full dataset, not filtered)
        $userStats = [
            'system_administrator' => User::whereHas('roles', function ($query) {
                $query->where('name', 'System Administrator');
            })->count(),
            'hr_head' => User::whereHas('roles', function ($query) {
                $query->where('name', 'HR Head');
            })->count(),
            'hr_staff' => User::whereHas('roles', function ($query) {
                $query->where('name', 'HR Staff');
            })->count(),
            'employee' => User::whereHas('roles', function ($query) {
                $query->where('name', 'Employee');
            })->count(),
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'users' => $users,
                'userStats' => $userStats,
                'html' => view('users.partials.user-list', compact('users'))->render(),
                'pagination' => view('users.partials.pagination', compact('users'))->render()
            ]);
        }

        return view('users.index', compact('users', 'userStats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = \Spatie\Permission\Models\Role::all();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:System Administrator,HR Head,HR Staff,Employee',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(), // Auto-verify email for admin created users
            ]);

            // Assign the selected role using Spatie
            $user->assignRole($request->role);
        });

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $roles = \Spatie\Permission\Models\Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:System Administrator,HR Head,HR Staff,Employee',
        ]);

        DB::transaction(function () use ($request, $user) {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Update user role using Spatie
            $user->syncRoles([$request->role]);
        });

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deleting the last System Administrator
        if ($user->role === 'system_admin') {
            $systemAdminCount = User::where('role', 'system_admin')->count();
            if ($systemAdminCount <= 1) {
                return redirect()->route('users.index')->with('error', 'Cannot delete the last System Administrator.');
            }
        }

        $user->delete();
        return redirect()->route('users.index');
    }

    /**
     * Generate user summary report.
     */
    public function generateSummary(Request $request)
    {
        $format = $request->input('export', 'pdf');

        // Build query based on filters
        $query = User::with('roles');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        if ($format === 'excel') {
            return $this->exportUserSummaryExcel($users);
        } else {
            return $this->exportUserSummaryPDF($users);
        }
    }

    /**
     * Export user summary as PDF
     */
    private function exportUserSummaryPDF($users)
    {
        $fileName = 'user_summary_' . date('Y-m-d_H-i-s') . '.pdf';

        // Calculate totals and statistics
        $totalUsers = $users->count();
        $activeUsers = $users->where('status', 'active')->count();
        $inactiveUsers = $users->where('status', 'inactive')->count();
        $verifiedUsers = $users->whereNotNull('email_verified_at')->count();

        // Create HTML content for PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>User Summary</title>
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; margin: 20px; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { margin: 0; color: #333; font-size: 18px; }
                .header p { margin: 5px 0; color: #666; font-size: 12px; }
                .stats { margin-bottom: 20px; }
                .stats table { width: 100%; border-collapse: collapse; }
                .stats td { padding: 8px; border: 1px solid #ddd; text-align: center; }
                .stats .label { background-color: #f8f9fa; font-weight: bold; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 9px; }
                th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .text-right { text-align: right; }
                .status-active { color: green; font-weight: bold; }
                .status-inactive { color: orange; font-weight: bold; }
                .status-suspended { color: red; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>User Summary Report</h1>
                <p>Generated on: ' . date('F j, Y g:i A') . '</p>
            </div>
            
            <div class="stats">
                <table>
                    <tr>
                        <td class="label">Total Users</td>
                        <td class="label">Active Users</td>
                        <td class="label">Inactive Users</td>
                        <td class="label">Verified Users</td>
                    </tr>
                    <tr>
                        <td>' . $totalUsers . '</td>
                        <td>' . $activeUsers . '</td>
                        <td>' . $inactiveUsers . '</td>
                        <td>' . $verifiedUsers . '</td>
                    </tr>
                </table>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($users as $user) {
            $statusClass = 'status-' . ($user->status ?? 'active');
            $roleName = $user->roles->first()->name ?? 'No Role';

            $html .= '
                    <tr>
                        <td>' . ($user->name ?: 'N/A') . '</td>
                        <td>' . $user->email . '</td>
                        <td>' . $roleName . '</td>
                        <td class="' . $statusClass . '">' . ucfirst($user->status ?? 'active') . '</td>
                        <td>' . $user->created_at->format('M d, Y') . '</td>
                        <td>' . ($user->last_login_at ? $user->last_login_at->format('M d, Y') : 'Never') . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>
        </body>
        </html>';

        // Use DomPDF to generate proper PDF
        try {
            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html);
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            // Fallback to simple HTML if DomPDF is not available
            return response($html, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'attachment; filename="' . str_replace('.pdf', '_report.html', $fileName) . '"',
            ]);
        }
    }

    /**
     * Export user summary as Excel
     */
    private function exportUserSummaryExcel($users)
    {
        $fileName = 'user_summary_' . date('Y-m-d_H-i-s') . '.csv';

        // Create CSV content with proper headers
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ];

        return response()->streamDownload(function () use ($users) {
            $output = fopen('php://output', 'w');

            // Write header row
            fputcsv($output, [
                'Name',
                'Email',
                'Role',
                'Status',
                'Created Date',
                'Email Verified',
                'Last Login'
            ]);

            // Write data rows
            foreach ($users as $user) {
                $roleName = $user->roles->first()->name ?? 'No Role';

                fputcsv($output, [
                    $user->name,
                    $user->email,
                    $roleName,
                    ucfirst($user->status ?? 'active'),
                    $user->created_at->format('M d, Y'),
                    $user->email_verified_at ? 'Yes' : 'No',
                    $user->last_login_at ? $user->last_login_at->format('M d, Y') : 'Never'
                ]);
            }
            fclose($output);
        }, $fileName, $headers);
    }
}
