<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TPMS') &mdash; EMREC</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=3">
    @stack('head')
</head>
<body>
@auth
@php($u = auth()->user())
<div class="app">
    <aside class="sidebar">
      <div class="sidebar-inner">
        <a href="{{ route('dashboard') }}" class="sidebar-brand" style="text-decoration:none;">
            <span class="mark">E</span> EMREC <span>TPMS</span>
        </a>
        <nav>
            <div class="nav-group">
                <div class="nav-label">Overview</div>
                <a href="{{ route('dashboard') }}" @class(['nav-item', 'active' => request()->routeIs('dashboard')])>@include('partials._icon', ['name' => 'dashboard']) Dashboard</a>
            </div>
            <div class="nav-group">
                <div class="nav-label">Pipeline</div>
                @php($tenderActive = request()->routeIs('tenders.*','opportunities.*'))
                <details class="nav-tree" @if($tenderActive) open @endif>
                    <summary @class(['nav-item', 'active' => $tenderActive])>
                        @include('partials._icon', ['name' => 'tender']) Tenders
                    </summary>
                    <a href="{{ route('opportunities.index') }}" @class(['nav-item', 'nav-sub', 'active' => request()->routeIs('opportunities.*')])>Opportunities</a>
                    <a href="{{ route('tenders.index') }}" @class(['nav-item', 'nav-sub', 'active' => request()->routeIs('tenders.*')])>Pipeline</a>
                </details>
                <a href="{{ route('service-requests.index') }}" @class(['nav-item', 'active' => request()->routeIs('service-requests.*')])>@include('partials._icon', ['name' => 'request']) Service Requests</a>
            </div>
            <div class="nav-group">
                <div class="nav-label">Delivery</div>
                <a href="{{ route('projects.index') }}" @class(['nav-item', 'active' => request()->routeIs('projects.*','milestones.*','feature-sets.*','tasks.*','scope-items.*')])>@include('partials._icon', ['name' => 'project']) Projects</a>
                <a href="{{ route('tasks.mine') }}" @class(['nav-item', 'active' => request()->routeIs('tasks.mine')])>@include('partials._icon', ['name' => 'mywork']) My Work</a>
                <a href="{{ route('tracker.index') }}" @class(['nav-item', 'active' => request()->routeIs('tracker.*')])>@include('partials._icon', ['name' => 'tracker']) Tracker</a>
            </div>
            <div class="nav-group">
                <div class="nav-label">Insights</div>
                <a href="{{ route('reports.index') }}" @class(['nav-item', 'active' => request()->routeIs('reports.*')])>@include('partials._icon', ['name' => 'reports']) Reports</a>
                @can('audit.view')
                    <a href="{{ route('audit.index') }}" @class(['nav-item', 'active' => request()->routeIs('audit.*')])>@include('partials._icon', ['name' => 'audit']) Audit log</a>
                @endcan
            </div>
            @canany(['users.manage', 'services.manage'])
            <div class="nav-group">
                <div class="nav-label">Admin</div>
                @can('users.manage')
                    <a href="{{ route('users.index') }}" @class(['nav-item', 'active' => request()->routeIs('users.*')])>@include('partials._icon', ['name' => 'team']) Team</a>
                    <a href="{{ route('import.create') }}" @class(['nav-item', 'active' => request()->routeIs('import.*')])>@include('partials._icon', ['name' => 'import']) Import</a>
                @endcan
                @can('services.manage')
                    <a href="{{ route('service-lines.index') }}" @class(['nav-item', 'active' => request()->routeIs('service-lines.*')])>@include('partials._icon', ['name' => 'services']) Service lines</a>
                @endcan
            </div>
            @endcanany
        </nav>
      </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <button type="button" class="sidebar-toggle" aria-label="Toggle navigation" onclick="document.body.classList.toggle('nav-open')">
                @include('partials._icon', ['name' => 'menu'])
            </button>
            <div class="topbar-title">@yield('title', 'Dashboard')</div>
            <div class="topbar-actions">
                <a href="{{ route('notifications.index') }}" class="icon-btn" title="Notifications">
                    @include('partials._icon', ['name' => 'bell'])
                    @php($unread = $u->unreadNotifications()->count())
                    @if ($unread)<span class="dot">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
                </a>
                <div class="user-chip"><b>{{ $u->name }}</b><span>{{ $u->role?->label() }}</span></div>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="ghost small">Sign out</button>
                </form>
            </div>
        </header>

        <main class="content">
            @if (session('status'))<div class="flash flash-ok">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="flash flash-err">{{ session('error') }}</div>@endif
            @if ($errors->any())
                <div class="flash flash-err">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <div class="scrim" onclick="document.body.classList.remove('nav-open')"></div>
</div>
@else
<main class="content" style="max-width:none;">
    @yield('content')
</main>
@endauth
@stack('foot')
</body>
</html>
