@extends('layouts.admin')

@section('title', '修正申請承認（管理者）')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/approve.css') }}">
@endsection

@section('content')
    <main class="admin-approve-detail">
        <div class="admin-approve-detail-inner">
            <h1 class="admin-approve-detail-title">勤怠詳細</h1>

            @php
                $attendance = $correctionRequest->attendance;
                $isApproved = $correctionRequest->status === \App\Models\CorrectionRequest::STATUS_APPROVED;

                $clockIn = optional($correctionRequest->requested_clock_in_at)->format('H:i');
                $clockOut = optional($correctionRequest->requested_clock_out_at)->format('H:i');
                $note = $correctionRequest->requested_note ?? '';
                $breaks = $correctionRequest->breaks ?? collect();
            @endphp

            <form class="admin-approve-detail-form"
                action="{{ route('stamp_correction_request.approve.confirm', ['attendance_correct_request_id' => $correctionRequest->id]) }}"
                method="POST">
                @csrf

                <div class="admin-approve-detail-table-wrap">
                    <table class="admin-approve-detail-table">
                        <tbody>
                            <tr>
                                <th>名前</th>
                                <td>{{ $correctionRequest->requestedBy->name }}</td>
                            </tr>

                            <tr>
                                <th>日付</th>
                                <td>{{ $attendance->work_date->format('Y年n月j日') }}</td>
                            </tr>

                            <tr>
                                <th>出勤・退勤</th>
                                <td>
                                    {{ $clockIn }} 〜 {{ $clockOut }}
                                </td>
                            </tr>

                            <tr>
                                <th>休憩</th>
                                <td>
                                    @forelse ($breaks as $row)
                                        {{ optional($row->break_start_at)->format('H:i') }}
                                        〜
                                        {{ optional($row->break_end_at)->format('H:i') }}
                                        <br>
                                    @empty
                                        休憩なし
                                    @endforelse
                                </td>
                            </tr>

                            <tr>
                                <th>備考</th>
                                <td>{{ $note }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="admin-approve-detail-actions">
                    @if ($isApproved)
                        <button class="admin-approve-detail-button admin-approve-detail-button--disabled" type="button"
                            disabled>
                            承認済み
                        </button>
                    @else
                        <button class="admin-approve-detail-button" type="submit">
                            承認
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </main>
@endsection
