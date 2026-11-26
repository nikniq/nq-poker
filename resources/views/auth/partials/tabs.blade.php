<div class="auth-head">
    <div class="auth-suits" aria-hidden="true"><span>♠</span><span class="r">♥</span><span>♣</span><span class="r">♦</span></div>
    <div class="auth-tabs" role="tablist">
        <a href="{{ route('login') }}" @class(['on' => request()->routeIs('login')])>Log in</a>
        <a href="{{ route('register') }}" @class(['on' => request()->routeIs('register')])>Sign up</a>
    </div>
</div>
