@extends('layouts.app')

@section('title', '申請一覧')

@section('content')
    <h1>申請一覧</h1>

    <p>一般ユーザー向けの申請一覧を表示する想定です。</p>

    <table>
        <thead>
            <tr>
                <th>状態</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>承認待ち（仮）</td>
                <td>2023/06/01（仮）</td>
                <td>遅延のため（仮）</td>
                <td>2023/06/02（仮）</td>
                <td>詳細（仮）</td>
            </tr>
        </tbody>
    </table>
@endsection
