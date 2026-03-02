<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class SysadminLoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $validUsername = env('SYSADMIN_USERNAME');
        $validPassword = env('SYSADMIN_PASSWORD');

        if ($request->username === $validUsername && Hash::check($request->password, $validPassword)) {
            $request->session()->put('sysadmin_authed', true);
            return redirect('/sysadmin');
        }

        return back()
            ->withInput($request->only('username'))
            ->withErrors(['username' => 'Invalid username or password.']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('sysadmin_authed');
        return redirect('/sysadmin');
    }

    private function checkAuth(Request $request)
    {
        if (!$request->session()->get('sysadmin_authed')) {
            abort(403);
        }
    }

    private function RunConsoleCommand($command)
    {
        Artisan::call($command);
        return Artisan::output();
    }

    public function migrate(Request $request)
    {
        $this->checkAuth($request);
        $output = $this->RunConsoleCommand('migrate');
        return redirect('/sysadmin')->with('console_output', $output);
    }

    public function migrate_rollback(Request $request)
    {
        $this->checkAuth($request);
        $output = $this->RunConsoleCommand('migrate:rollback');
        return redirect('/sysadmin')->with('console_output', $output);
    }

    public function migrate_fresh(Request $request)
    {
        $this->checkAuth($request);
        $output = $this->RunConsoleCommand('migrate:fresh');
        return redirect('/sysadmin')->with('console_output', $output);
    }

    public function migrate_fresh_seed(Request $request)
    {
        $this->checkAuth($request);
        $output = $this->RunConsoleCommand('migrate:fresh --seed');
        return redirect('/sysadmin')->with('console_output', $output);
    }
}
