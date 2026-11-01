<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);
    }

    public function index()
    {
        $this->authorizeAdmin();

        return view('admin.index', [
            'users' => User::with('branch')->orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'departments' => Department::orderBy('branch_type')->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function storeUser(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['super_admin', 'branch_admin', 'it_support'])],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);
        if ($data['role'] !== 'super_admin' && !$data['branch_id']) {
            return back()->withErrors(['branch_id' => 'A branch is required for non-super-admin users.'])->withInput();
        }
        if ($data['role'] === 'super_admin') $data['branch_id'] = null;
        $data['password'] = Hash::make($data['password']);
        User::create($data + ['is_active' => true]);
        return back()->with('success', 'User created.');
    }

    public function updateUser(Request $request, User $user)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['super_admin', 'branch_admin', 'it_support'])],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['required', 'boolean'],
        ]);
        if ($user->is(auth()->user()) && !$request->boolean('is_active')) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.'])->withInput();
        }
        if ($data['role'] !== 'super_admin' && !$data['branch_id']) {
            return back()->withErrors(['branch_id' => 'A branch is required for non-super-admin users.'])->withInput();
        }
        if ($data['role'] === 'super_admin') $data['branch_id'] = null;
        if (!$data['password']) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);
        $user->update($data);
        return back()->with('success', 'User updated.');
    }

    public function storeBranch(Request $request)
    {
        $this->authorizeAdmin();
        Branch::create($request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:branches,name'],
            'type' => ['required', Rule::in(['head_office', 'school'])],
        ]) + ['is_active' => true]);
        return back()->with('success', 'Branch created.');
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        $this->authorizeAdmin();
        $branch->update($request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('branches', 'name')->ignore($branch->id)],
            'type' => ['required', Rule::in(['head_office', 'school'])],
            'is_active' => ['required', 'boolean'],
        ]));
        return back()->with('success', 'Branch updated.');
    }

    public function storeDepartment(Request $request)
    {
        $this->authorizeAdmin();
        Department::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_type' => ['required', Rule::in(['head_office', 'school'])],
        ]) + ['is_active' => true]);
        return back()->with('success', 'Department created.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $this->authorizeAdmin();
        $department->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_type' => ['required', Rule::in(['head_office', 'school'])],
            'is_active' => ['required', 'boolean'],
        ]));
        return back()->with('success', 'Department updated.');
    }

    public function storeCategory(Request $request)
    {
        $this->authorizeAdmin();
        Category::create($request->validate(['name' => ['required', 'string', 'max:255', 'unique:categories,name']]) + ['is_active' => true]);
        return back()->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $this->authorizeAdmin();
        $category->update($request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'is_active' => ['required', 'boolean'],
        ]));
        return back()->with('success', 'Category updated.');
    }
}
