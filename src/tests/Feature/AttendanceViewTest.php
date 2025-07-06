<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Rest;

class AttendanceViewTest extends TestCase
{
    use RefreshDatabase;

    // 自分の勤怠情報を全て表示
    public function test_view_all_attendance_display()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('attendance_list'))
            ->assertSee($attendance->start_time->format('H:i'));
    }

    // attendance_listで現在の月が表示される
    public function test_attendance_list_display_current_month()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('attendance_list'))
            ->assertSee(now()->format('Y/n'));
    }

    // 前月の勤怠情報が表示
    public function test_attendance_list_display_previous_mouth()
    {
        $user = User::factory()->create();

        // 前月の年月日を作成
        $previousMonthDate = now()->subMonth(); //先月の同日

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $previousMonthDate->toDateString(),
            'start_time' => $previousMonthDate->copy()->setTime(9,0),
            'end_time' => $previousMonthDate->copy()->setTime(10,0),
        ]);

        $this->actingAs($user)
            // 前月ボタンで前月ページに遷移
            ->get(route('attendance_list', [
                'year' => $previousMonthDate->year,
                'month' => $previousMonthDate->month,
            ]))
            ->assertSee($attendance->start_time->format('H:i'))
            ->assertSee($attendance->end_time->format('H:i'));
    }

    // 翌月の勤怠情報が表示
    public function test_attendance_list_display_next_mouth()
    {
        $user = User::factory()->create();

        // 翌月の年月日を作成
        $nextMonthDate = now()->addMonth(); //翌月の同日

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $nextMonthDate->toDateString(),
            'start_time' => $nextMonthDate->copy()->setTime(9,0),
            'end_time' => $nextMonthDate->copy()->setTime(10,0),
        ]);

        $this->actingAs($user)
            // 前月ボタンで前月ページに遷移
            ->get(route('attendance_list', [
                'year' => $nextMonthDate->year,
                'month' => $nextMonthDate->month,
            ]))
            ->assertSee($attendance->start_time->format('H:i'))
            ->assertSee($attendance->end_time->format('H:i'));
    }

    // 詳細ボタンを押すとattendance_detailに遷移
    public function test_go_to_attendance_detail()
    {
        $user =User::factory()->create();

        // 勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance_detail', ['attendance_id' => $attendance->id]));

        $response->assertStatus(200);
    }

    // attendance_detailにユーザー名が表示
    public function test_attendance_detail_display_is_user_name()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('attendance_detail', ['attendance_id' => $attendance->id]))
            ->assertSee($user->name);
    }

    // attendance_detailに選択した日付が表示
    public function test_attendance_detail_display_is_selected_date()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('attendance_detail', ['attendance_id' => $attendance->id]))
            ->assertSee(\Carbon\Carbon::parse($attendance->date)->format('Y年m月d日'));
    }


    // attendance_detailに勤怠情報が表示
    public function test_attendance_information_is_display_in_attendance_detail()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('attendance_detail', ['attendance_id' => $attendance->id]))
            ->assertSee($attendance->start_time->format('H:i'))
            ->assertSee($attendance->end_time->format('H:i'));
    }

    // attendance_detailに休憩情報が表示
    public function test_rest_information_is_display_in_attendance_detail()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'date' => now()->toDateString(),
            'break_start_time' => now(),
            'break_end_time' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('attendance_detail', ['attendance_id' => $attendance->id]))
            ->assertSee($rest->break_start_time->format('H:i'))
            ->assertSee($rest->break_end_time->format('H:i'));
    }

    // staffが退勤時間より遅く出勤時間を修正した場合のエラー
    public function test_attendance_validation_error_when_end_time_is_later_than_start_time()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $response = $this->post(route('attendance.request_edit', ['attendance_id' => $attendance->id]), [
                'start_time' => '18:00',
                'end_time' => '10:00',
                'date' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors(['start_time']);
        $this->assertStringContainsString('出勤時間もしくは退勤時間が不適切な値です', session('errors')->first('start_time'));
    }

    // 休憩終了時間より遅く休憩開始時間を修正した場合のエラー
    public function test_attendance_validation_error_when_break_end_time_is_later_than_break_start_time()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $rest = REst::create([
            'attendance_id' => $attendance->id,
            'date' => now()->toDateString(),
            'bread_start_time' => now(),
            'break_end_time' => now(),
        ]);

        $response = $this->post(route('attendance.request_edit', ['attendance_id' => $attendance->id]), [
                'start_time' => '09:00',
                'end_time' => '22:00',
                'break_start_time' => ['18:00'],
                'break_end_time' => ['10:00'],
                'reason' => 'テストエラー確認',
            ]);

        $response->assertSessionHasErrors(['break_start_time.0']);
        $this->assertStringContainsString('休憩開始が終了より後になっています', session('errors')->first('break_start_time.0'));
    }

    // reasonを未入力のエラー
    public function test_attendance_validation_error_when_reason_is_empty()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
            'reason' => '',
        ]);

        $response = $this->post(route('attendance.request_edit', ['attendance_id' => $attendance->id]));

        $response->assertSessionHasErrors('reason');
    }

    // 修正申請成功
    public function test_attendance_edit_is_success()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $response = $this->post(route('attendance.request_edit', ['attendance_id' => $attendance->id]), [
            'start_time' => '09:00',
            'end_time' => '20:00',
            'break_start_time' => ['12:00'],
            'break_end_time' => ['13:00'],
            'reason' => 'テスト',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('attendance_requests', [
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_start_time' => now()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'requested_end_time' => now()->setTime(20, 0)->format('Y-m-d H:i:s'),
            'requested_reason' => 'テスト',
        ]);
    }

    // adminが修正内容を確認し、承認する
    public function test_admin_checks_the_requested_data()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $start = now()->setTime(9, 0, 0);
        $end = now()->setTime(18, 0, 0);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);

        $attendance_requests = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_start_time' => $start,
            'requested_end_time' => $end,
            'requested_reason' => 'test',
        ]);

        $this->actingAs($admin)
            ->get(route('request_list'))
            ->assertSee('test');
    }

    public function test_request_list_display_the_my_request_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $startTimeOnly = now()->setTime(9, 0)->format('H:i');
        $endTimeOnly = now()->setTime(18, 0)->format('H:i');

        $start = now()->setTime(9, 0)->format('Y-m-d H:i');
        $end = now()->setTime(18, 0)->format('Y-m-d H:i');

        $this->post(route('attendance.request_edit', ['attendance_id' => $attendance->id]), [
            'start_time' => $startTimeOnly,
            'end_time' => $endTimeOnly,
            'reason' => 'テスト',
            'break_start_time' => [], // ← 追加（空でOK）
            'break_end_time' => [],
        ]);

        $this->assertDatabaseHas('attendance_requests',[
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_start_time' => $start,
            'requested_end_time' => $end,
            'requested_reason' => 'テスト',
        ]);

        $this->get(route('request_list', ['status' => 'pending']))
            ->assertSee('テスト')
            ->assertSee('申請中')
            ->assertSee($user->name);
    }

    // 承認済みデータ閲覧
    public function test_request_list__display_is_approved_data()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $date = now()->toDateString();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'start_time' => now(),
        ]);

        // 修正申請を送信
        $this->post(route('attendance.request_edit', ['attendance_id' => $attendance->id]), [
            'start_time' => '09:00',
            'end_time' => '21:00',
            'reason' => 'test',
            'break_start_time' => [],
            'break_end_time' => [],
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_start_time' => $date . ' 09:00:00',
            'requested_end_time' => $date . ' 21:00:00',
            'requested_reason' => 'test',
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        $this->post(route('admin.attendance.approve', ['attendance_id' => $attendance->id]));

        $this->get(route('request_list', ['status' => 'approved']))
            ->assertSee('承認済み')
            ->assertSee('test');
    }

    //申請確認画面
    public function test_go_to_requested_confirm()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $date = now()->toDateString();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'start_time' => now(),
        ]);

        // 修正申請を送信
        $this->post(route('attendance.request_edit', ['attendance_id' => $attendance->id]), [
            'start_time' => '09:00',
            'end_time' => '21:00',
            'reason' => 'test',
            'break_start_time' => [],
            'break_end_time' => [],
        ])->assertRedirect();

        $request = AttendanceRequest::where('attendance_id', $attendance->id)->latest()->first();

        $this->get(route('request_list', ['status' => 'pending']))
            ->assertSee('詳細');

        $this->get(route('requested_confirm', ['request_id' => $request->id]))
            ->assertStatus(200);


    }
}
