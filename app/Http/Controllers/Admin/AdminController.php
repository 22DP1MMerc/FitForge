<?php
// app/Http/Controllers/Admin/AdminController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * List all users with their stats
     */
    public function users(Request $request)
    {
        $query = User::withCount(['routines', 'goals', 'workoutLogs']);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $users = $query->latest()->paginate(20);
        
        // Add query parameters to pagination links
        $users->appends($request->all());
        
        // Calculate stats for the header
        $stats = [
            'total_users' => User::count(),
            'total_admins' => User::where('is_admin', true)->count(),
            'active_users_7d' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];
        
        return Inertia::render('Admin/Users', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }
    
    /**
     * View a specific user's details
     */
    public function showUser(User $user)
    {
        // Load user with counts
        $user->loadCount(['routines', 'goals', 'workoutLogs']);
        
        // Load user's recent activities
        $recentRoutines = $user->routines()->latest()->take(5)->get();
        $recentGoals = $user->goals()->latest()->take(5)->get();
        $recentWorkouts = $user->workoutLogs()->with('routine')->latest()->take(5)->get();
        
        return Inertia::render('Admin/UserDetails', [
            'user' => $user,
            'recent_routines' => $recentRoutines,
            'recent_goals' => $recentGoals,
            'recent_workouts' => $recentWorkouts,
        ]);
    }
    
    /**
     * Promote or demote a user to/from admin
     */
    public function toggleAdmin(User $user)
    {
        // Prevent removing admin status from the last admin
        $adminCount = User::where('is_admin', true)->count();
        
        if ($user->is_admin && $adminCount <= 1) {
            return back()->with('error', 'Cannot remove the last admin user.');
        }
        
        $user->update(['is_admin' => !$user->is_admin]);
        
        $message = $user->is_admin 
            ? "{$user->name} is now an administrator." 
            : "Admin privileges removed from {$user->name}.";
            
        return back()->with('success', $message);
    }
    
    /**
     * Delete a user and all their associated data
     */
    public function deleteUser(User $user)
    {
        // Prevent deleting the last admin
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'Cannot delete the last admin user.');
        }
        
        $userName = $user->name;
        $user->delete();
        
        return back()->with('success', "User {$userName} has been permanently deleted.");
    }
}
