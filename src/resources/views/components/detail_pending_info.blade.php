<div class="tab-panel">

    <input class="original__tab-switch" type="radio" name="tab__name" id="tab__original" checked>
    <label class="tab-label original" for="tab__original">申請前</label>

    <input class="pending__tab-switch" type="radio" name="tab__name" id="tab__pending">
    <label class="tab-label pending" for="tab__pending">申請内容</label>

    <div class="border"></div>

    <div class="original-info">
        <div class="content">
            <table class="detail__table">

                <colgroup>
                    <col class="col__left">
                    <col class="col__center" span="3">
                    <col class="col__right">
                </colgroup>

                @if(session('success'))
                    <tr class="message__table-row success">
                        <td class="message" colspan="5">{{ session('success') }}</td>
                    </tr>
                @endif

                <tr class="detail__table-row">
                    <td class="label">名前</td>
                    <td class="user-name" colspan="3">{{ $staff->name }}</td>
                    <td></td>
                </tr>

                <tr class="detail__table-row">
                    <td class="label">日付</td>
                    <td class="parameter">{{ $date->year }}年</td>
                    <td></td>
                    <td class="parameter">{{ $date->month }}月{{ $date->day }}日</td>
                    <td></td>
                </tr>

                <tr class="detail__table-row">
                    <td class="label">出勤・退勤</td>
                    <td class="parameter">{{ $attendance && $attendance->clock_in ? $attendance->clock_in->format('H:i') : '' }}</td>
                    <td class="parameter">〜</td>
                    <td class="parameter">{{ $attendance && $attendance->clock_out ? $attendance->clock_out->format('H:i') : '' }}</td>
                    <td></td>
                </tr>

                @if($attendance)
                    @foreach($attendance->rests as $rest)
                        <tr class="detail__table-row">
                            <td class="label">{{ $loop->first ? '休憩' : '休憩'.$loop->iteration }}</td>
                            <td class="parameter">{{ $rest && $rest->start_time ? $rest->start_time->format('H:i') : '' }}</td>
                            <td class="parameter">〜</td>
                            <td class="parameter">{{ $rest && $rest->end_time ? $rest->end_time->format('H:i') : '' }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                @else
                    <tr class="detail__table-row">
                        <td class="label">休憩</td>
                        <td></td>
                        <td class="parameter">〜</td>
                        <td></td>
                        <td></td>
                    </tr>
                @endif

            </table>
        </div>

        @if($pendingRequest->is_deletion)
            <p class="status">*削除承認待ちのため修正はできません。</p>
        @else
            <p class="status">*承認待ちのため修正はできません。</p>
        @endif

    </div>

    <div class="pending-info">
        <div class="content">
            <table class="detail__table">

                <colgroup>
                    <col class="col__left">
                    <col class="col__center" span="3">
                    <col class="col__right">
                </colgroup>

                <tr class="detail__table-row">
                    <td class="label">名前</td>
                    <td class="user-name" colspan="3">{{ $staff->name }}</td>
                    <td></td>
                </tr>

                <tr class="detail__table-row">
                    <td class="label">日付</td>
                    <td class="parameter">{{ $date->year }}年</td>
                    <td></td>
                    <td class="parameter">{{ $date->month }}月{{ $date->day }}日</td>
                    <td></td>
                </tr>

                @if($pendingRequest->is_deletion)
                    <tr class="detail__table-row">
                        <td class="label">勤怠</td>
                        <td class="reason-text" colspan="3"><span>勤怠情報の削除申請です</span></td>
                        <td></td>
                    </tr>
                @endif

                @if(isset($requestDetails['App\Models\Attendance']))
                    @php $att = $requestDetails['App\Models\Attendance']->first(); @endphp
                    <tr class="detail__table-row">
                        <td class="label">出勤・退勤</td>
                        <td class="parameter">{{ $att->start_time->format('H:i') }}</td>
                        <td class="parameter">〜</td>
                        <td class="parameter">{{ $att->end_time->format('H:i') }}</td>
                        <td></td>
                    </tr>
                @endif

                @if(isset($requestDetails['App\Models\Rest']))
                    @foreach($requestDetails['App\Models\Rest'] as $rest)
                        <tr class="detail__table-row">
                            <td class="label">{{ $loop->first ? '休憩' : '休憩'.$loop->iteration }}</td>

                            @if(!$rest->start_time && !$rest->end_time)
                                <td class="reason-text" colspan="3"><span>休憩を取消します</span></td>
                            @else
                                <td class="parameter">
                                    {{ $rest->start_time->format('H:i') }}
                                </td>
                                <td class="parameter">〜</td>
                                <td class="parameter">
                                    {{ $rest->end_time->format('H:i') }}
                                </td>
                            @endif

                            <td></td>
                        </tr>
                    @endforeach
                @endif

                <tr class="detail__table-row">
                    <td class="label">備考</td>
                    <td class="reason-text" colspan="3">{{ $pendingRequest->reason }}</td>
                    <td></td>
                </tr>

            </table>
        </div>

        @if($pendingRequest->is_deletion)
            <p class="status">*削除承認待ちのため修正はできません。</p>
        @else
            <p class="status">*承認待ちのため修正はできません。</p>
        @endif

    </div>

</div>