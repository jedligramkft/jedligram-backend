<form method="POST" action="/login">
    @csrf
    @if($errors->any())
        <p style="color:red;">{{ $errors->first() }}</p>
    @endif
    <div>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus>
    </div>
    <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit">Log in</button>
</form>
