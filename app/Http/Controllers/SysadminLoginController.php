<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Services\ConsoleHistoryService;

class SysadminLoginController extends Controller
{
    public function __construct(private ConsoleHistoryService $history) {}

    public function page(Request $request)
    {
        return view('sysadmin.page', [
            'consoleHistory' => $request->session()->get('sysadmin_authed')
                ? $this->history->getAll()
                : [],
        ]);
    }
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $validUsername = env('SYSADMIN_USERNAME');
        $validPassword = env('SYSADMIN_PASSWORD');

        if ($request->username === $validUsername && Hash::check($request->password, $validPassword)) {
            $request->session()->regenerate(); // prevent session fixation
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

    private function RunConsoleCommand($command): array
    {
        set_time_limit(0);

        // Auto-add --force for destructive commands that prompt for confirmation
        $forcedCommands = ['migrate:fresh', 'migrate:rollback', 'migrate:reset', 'migrate:refresh', 'db:wipe'];
        foreach ($forcedCommands as $cmd) {
            if (str_starts_with($command, $cmd) && !str_contains($command, '--force')) {
                $command .= ' --force';
                break;
            }
        }

        $exitCode = Artisan::call($command);
        $output = Artisan::output();

        return [
            'exit_code' => $exitCode,
            'output' => $output,
            'command' => $command,
        ];
    }

    public function RunCommand(Request $request)
    {
        $this->checkAuth($request);

        $command = $request->query('cmd');
        $force = $request->input('force', '0');
        $command = str_replace('+', ' ', $command);

        // If the UI asked to force the command, append --force when it's not present.
        if ($force && $force !== '0' && !str_contains($command, '--force')) {
            $command .= ' --force';
        }

        try {
            $result = $this->RunConsoleCommand($command);
            $success = $result['exit_code'] === 0;

            $this->history->add([
                'command'   => $result['command'],
                'output'    => $result['output'],
                'success'   => $success,
                'exit_code' => $result['exit_code'],
                'timestamp' => now()->toTimeString(),
            ]);

            return response()->json([
                'success'   => $success,
                'command'   => $result['command'],
                'output'    => $result['output'],
                'exit_code' => $result['exit_code'],
            ]);
        } catch (\Throwable $e) {
            $this->history->add([
                'command'   => $command,
                'output'    => $e->getMessage(),
                'success'   => false,
                'exit_code' => 1,
                'timestamp' => now()->toTimeString(),
            ]);

            return response()->json([
                'success'   => false,
                'command'   => $command,
                'output'    => $e->getMessage(),
                'exit_code' => 1,
            ]);
        }
    }

    public function clearHistory(Request $request)
    {
        $this->checkAuth($request);
        $this->history->clear();
        return response()->json(['ok' => true]);
    }
}
