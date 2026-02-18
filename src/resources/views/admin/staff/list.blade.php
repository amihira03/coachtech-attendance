@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/staff-list.css') }}">
@endsection

@section('content')
    <div class="admin-staff">
        <div class="admin-staff-inner">
            <h1 class="admin-staff-title">スタッフ一覧</h1>

            <div class="admin-staff-table-wrap">
                <table class="admin-staff-table">
                    <thead>
                        <tr>
                            <th class="admin-staff-th">名前</th>
                            <th class="admin-staff-th">メールアドレス</th>
                            <th class="admin-staff-th">月次勤怠</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="admin-staff-td">{{ $user->name }}</td>
                                <td class="admin-staff-td">{{ $user->email }}</td>
                                <td class="admin-staff-td">
                                    <a class="admin-staff-detail-link"
                                        href="{{ route('admin.attendance.staff', ['id' => $user->id]) }}">
                                        詳細
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="admin-staff-empty-box" colspan="3">
                                    該当するスタッフがいません
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
