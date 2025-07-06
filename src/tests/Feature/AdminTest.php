<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceRequest;


class AdminTest extends TestCase
{
    use RefreshDatabase;

    // adminがstaffの出勤を確認
    public function test_admin_check_start_time()
    {
        // Userテーブルのroleカラムを変更し、それぞれのユーザーを作成
        $staff = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $attendance = Attendance::create([
            'user_id' =>$staff->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        // 管理者としてログイン
        $this->actingAs($admin)
            ->get('/admin/attendance_list')
            ->assertSee($attendance->start_time->format('H:i'));
    }

    // 休憩時間の確認
    public function test_admin_check_break_time()
    {
        $staff = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);
        $rests = Rest::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => now()->setTime(12,0),
            'break_end_time' => now()->setTime(13,0),
        ]);

        // 期待する休憩時間
        $expectedBreakTime = '1：00';


        $this->actingAs($admin)
            ->get('/admin/attendance_list')
            ->assertSee($expectedBreakTime);
    }

    // 退勤確認
    public function test_admin_check_end_time()
    {
        $staff = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance_list')
            ->assertSee($attendance->end_time->format('H:i'));
    }

    // adminページのattendance_list表示
    public function test_attendance_data_display_for_all_users()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendance_list'))
            ->assertSee($attendance->start_time->format('H:i'))
            ->assertSee($attendance->end_time->format('H:i'));
    }

    // adminページのattendance_listで当日の日付が表示
    public function test_attendance_data_display_is_to_date()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendance_list'))
            ->assertSee(\Carbon\Carbon::parse($attendance->date)->format('Y/m/d'));
    }

    // attendance_listで先月のデータを確認
    public function test_admin_is_display_previous_month()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $targetDate = now()->subMonth()->startOfMonth();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $targetDate->toDateString(),
            'start_time' => $targetDate->copy()->setTime(9,0),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendance_list', ['date' => $targetDate->toDateString() ]))
            ->assertSee($user->name)
            ->assertSee($attendance->start_time->format('H:i'));

    }

    // attendance_listで翌月のデータを確認
    public function test_admin_is_display_next_month()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $targetDate = now()->addMonth()->startOfMonth();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $targetDate->toDateString(),
            'start_time' => $targetDate->copy()->setTime(9,0),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendance_list', ['date' => $targetDate->toDateString() ]))
            ->assertSee($user->name)
            ->assertSee($attendance->start_time->format('H:i'));

    }

    // 詳細ページに遷移した際日付は一緒か確認
    public function test_go_to_attendance_detail()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);


        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('attendance_detail', ['attendance_id' => $attendance->id]))
            ->assertSee($attendance->date->format('Y年m月d日'));
    }

    // 勤務開始時間より前に勤務終了時間が入力されているエラー
    public function test_attendance_validation_error_when_end_time_is_later_than_start_time()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($admin);

        $response = $this->from(route('attendance_detail', ['attendance_id' => $attendance->id]))
        ->post(route('attendance_update', ['attendance_id' => $attendance->id]), [
                'start_time' => '15:00',
                'end_time' => '10:00',
                'reason' => 'test',
        ]);

        $response->assertSessionHasErrors(['start_time']);

        $errors = session('errors');
        $this->assertTrue($errors->has('start_time'));
        $this->assertStringContainsString('出勤時間もしくは退勤時間が不適切な値です', $errors->first('start_time'));
    }

    // 休憩開始時間が勤務終了時間の後のエラー
    public function test_attendance_validation_error_when_end_time_is_later_than_break_start_time()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);


        $this->actingAs($admin);

        $response = $this->from(route('attendance_detail', ['attendance_id' => $attendance->id]))
        ->post(route('attendance_update', ['attendance_id' => $attendance->id]), [
                'start_time' => '10:00',
                'end_time' => '19:00',
                'break_start_time' => ['20:00'],
                'break_end_time' => ['21:00'],
                'reason' => 'test',
        ]);

        $response->assertSessionHasErrors(['break_end_time.0']);

        $errors = session('errors');
        $this->assertTrue($errors->has('break_end_time.0'));
        $this->assertStringContainsString('休憩終了時間が勤務時間外です', $errors->first('break_end_time.0'));
    }

    // 備考未入力エラー
    public function test_attendance_validation_error_when_reason_empty()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($admin);

        $response = $this->from(route('attendance_detail', ['attendance_id' => $attendance->id]))
        ->post(route('attendance_update', ['attendance_id' => $attendance->id]), [
                'start_time' => '10:00',
                'end_time' => '22:00',
                'break_start_time' => ['20:00'],
                'break_end_time' => ['21:00'],
                'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');

        $errors = session('errors');
        $this->assertTrue($errors->has('reason'));
        $this->assertStringContainsString('備考を記入してください', $errors->first('reason'));
    }

    public function test_go_to_staff_list()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.staff_list'))
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_go_to_staff_attendance_list()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.staff_attendance_list', ['user_id' => $user->id]))
            ->assertSee($attendance->start_time->format('H:i'));
    }

    // staff_attendance_listで先月ページ閲覧
    public function test_staff_attendance_list_display_previous_month()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $previousMonth = now()->subMonth();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $previousMonth->toDateString(),
            'start_time' => $previousMonth->copy()->setTime(9,0),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.staff_attendance_list', [
                'user_id' => $user->id,
                'year' => $previousMonth->year,
                'month' => $previousMonth->month,
            ]))
            ->assertSee($attendance->start_time->format('H:i'));
    }

    // staff_attendance_listで翌月ページ閲覧
    public function test_staff_attendance_list_display_next_month()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $NextMonth = now()->addMonth();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $NextMonth->toDateString(),
            'start_time' => $NextMonth->copy()->setTime(9,0),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.staff_attendance_list', [
                'user_id' => $user->id,
                'year' => $NextMonth->year,
                'month' => $NextMonth->month,
            ]))
            ->assertSee($attendance->start_time->format('H:i'));
    }

    // admin.attendance_listからattendance_detail
    public function test_transition_from_admin_attendance_list_to_attendance_detail()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('attendance_detail', ['attendance_id' => $attendance->id]))
            ->assertSee($attendance->date->format('Y年m月d日'));
    }

    // adminがrequest_listの申請中ページを表示
    public function test_admin_go_to_request_list_pending()
    {
        $admin = User::factory()->create(['is_admin' => true ]);
        $user = User::factory()->create(['is_admin' => false ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('attendance.request_edit', ['attendance_id' => $attendance->id]), [
                'start_time' => '09:00',
                'end_time' => '19:00',
                'break_start_time' => [],
                'break_emd_time' => [],
                'reason' => 'テスト',
            ])->assertRedirect();

        $this->actingAs($admin)
            ->get(route('request_list', ['status' => 'pending']))
            ->assertSee('申請中')
            ->assertSee('テスト');
    }


    // adminがrequest_listの承認済みページを表示
    public function test_admin_go_to_request_list_approved()
    {
        $admin = User::factory()->create(['is_admin' => true ]);
        $user = User::factory()->create(['is_admin' => false ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        // スタッフが申請
        $request = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time' => Carbon::today()->setTime(10, 0),
            'requested_breaks' => [],
            'requested_reason' => 'テスト',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->get(route('request_list', ['status' => 'approved']))
            ->assertSee('承認済み')
            ->assertSee('テスト');
    }


    // adminがrequested_confirmページを表示
    public function test_admin_go_to_requested_confirm()
    {
        $admin = User::factory()->create(['is_admin' => true ]);
        $user = User::factory()->create(['is_admin' => false ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        // スタッフが申請
        $request = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time' => Carbon::today()->setTime(10, 0),
            'requested_breaks' => [],
            'requested_reason' => 'テスト',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('requested_confirm', ['request_id' => $request->id]))
            ->assertSee($user->name)
            ->assertSee('承認')
            ->assertSee('テスト');
    }


    // 承認機能とattendanceテーブルの更新確認
    public function test_admin_approved_attendance_data()
    {
        $admin = User::factory()->create(['is_admin' => true ]);
        $user = User::factory()->create(['is_admin' => false ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        // スタッフが申請
        $request = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_start_time' => Carbon::today()->setTime(9, 0),
            'requested_end_time' => Carbon::today()->setTime(10, 0),
            'requested_breaks' => [],
            'requested_reason' => 'テスト',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
        ->post(route('admin.attendance.approve', ['attendance_id' => $attendance->id]));

        $response->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $user->id,
            'start_time' => Carbon::today()->setTime(9, 0)->toDateTimeString(),
            'end_time' => Carbon::today()->setTime(10, 0)->toDateTimeString(),
            'reason' => 'テスト',
        ]);
    }

    
}
