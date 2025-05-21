<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>work-schedule</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    @yield('css')
</head>

<body>
    <div class="header">
        <div class="header-inner">
            <div class="header-title">
                <img src="{{ asset('images/CoachTech_White.png') }}" alt="COACHTECH" class="header-title_logo">
            </div>
            @auth
                @if(Auth::user()->is_admin)
                    <div class="header-menu">
                        <div class="header-menu_link">
                            <a href="{{ route('admin.attendance_list') }}" class="header-link">
                                勤怠一覧
                            </a>
                        </div>
                        <div class="header-menu_link">
                            <a href="{{ route('admin.staff_list') }}" class="header-link">
                                スタッフ一覧
                            </a>
                        </div>
                        <div class="header-menu_link">
                            <a href="{{ route('admin.request_list') }}" class="header-link">
                                申請一覧
                            </a>
                        </div>
                        <div class="header-menu">
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <input type="submit" value="ログアウト" class="header-link">
                            </form>
                        </div>
                    </div>
                @else
                    <div class="header-menu">
                        <div class="header-menu_link">
                            <a href="{{ route('attendance') }}" class="header-link">
                                勤怠
                            </a>
                        </div>
                        <div class="header-menu_link">
                            <a href="{{ route('attendance_list') }}" class="header-link">
                                勤怠一覧
                            </a>
                        </div>
                        <div class="header-menu_link">
                            <a href="{{ route('request_list') }}" class="header-link">
                                申請
                            </a>
                        </div>
                        <div class="header-menu">
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <input type="submit" value="ログアウト" class="header-link">
                            </form>
                        </div>
                    </div>
                @endif
            @endauth
        </div>
    </div>

    <div class="content">
        @yield('content')
    </div>


</body>
</html>