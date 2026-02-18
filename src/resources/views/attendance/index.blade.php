@extends('layouts.app')

@section('title', '勤怠')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
    <main class="attendance-index">
        <div class="attendance-index-inner">

            {{-- ステータス（灰色ピル） --}}
            <p class="attendance-index-status">{{ $statusText }}</p>

            {{-- 日付 --}}
            <p class="attendance-index-date">{{ $now->format('Y年n月j日') }}（{{ $now->isoFormat('ddd') }}）</p>

            {{-- 時刻（大きく） --}}
            <p class="attendance-index-time">{{ $now->format('H:i') }}</p>


            {{-- 退勤時メッセージ（Figmaの位置：時刻の下） --}}
            @if (session('message'))
                <p class="attendance-index-message">{{ session('message') }}</p>
            @endif

            {{-- ボタン --}}
            <form class="attendance-index-form" action="{{ route('attendance.store') }}" method="POST">
                @csrf

                <div class="attendance-index-actions">
                    @if ($showClockIn)
                        <button class="attendance-index-button attendance-index-button--primary" type="submit"
                            name="action" value="clock_in">
                            出勤
                        </button>
                    @endif

                    @if ($showClockOut)
                        <button class="attendance-index-button attendance-index-button--primary" type="submit"
                            name="action" value="clock_out">
                            退勤
                        </button>
                    @endif

                    @if ($showBreakStart)
                        <button class="attendance-index-button attendance-index-button--ghost" type="submit" name="action"
                            value="break_start">
                            休憩入
                        </button>
                    @endif

                    @if ($showBreakEnd)
                        <button class="attendance-index-button attendance-index-button--ghost" type="submit" name="action"
                            value="break_end">
                            休憩戻
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </main>
@endsection
