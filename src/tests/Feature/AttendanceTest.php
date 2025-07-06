<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    // attendance画面の表示
    public function test_staff_display_the_attendance_view()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');

        // ステータスコード 200（正常にページが表示したか）
        $response->assertStatus(200);
        $response->assertViewIs('attendance');
    }

    // 勤務外と表示
    public function test_attendance_status_before_work()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('勤務外');
    }

    // 勤務中と表示
    public function test_attendance_status_working()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => null,
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('勤務中');
    }

    // 休憩中と表示
    public function test_attendance_status_on_break()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // attendanceテーブルで出勤中に設定
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => null,
        ]);

        // restテーブルで休憩開始に設定
        $attendance->rests()->create([
            'break_start_time' => now(),
            'break_end_time' => null,
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    // 退勤済みと表示
    public function test_attendance_status_finished()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    // 出勤機能
    public function test_staff_start_at_work()
    {
        // テスト用ユーザーを作成し、ログイン状態にする。
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('出勤');

        // 出勤用のPOSTリクエストを送る
        $response = $this->post('/attendance/start');

        // 打刻データがDBに保存されているか確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => now()->toDateString(), //今日の日付が取得できているか
        ]);

        // レスポンスがリダイレクトかを確認
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');
    }

    // 出勤は一日一回のみ
    public function test_rest_only_start_time_once()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => 'finished',
            'start_time' => now(),
            'end_time' => now(),
        ]);

        $response = $this->get('/attendance');

        // 出勤ボタンが非表示 (>< は文字の部分一致をさけるため)
        $response->assertDontSee([ '>出勤<', '>休憩入<', '>休憩戻<', '>退勤<' ]);
    }


    // 休憩入ボタンの機能確認
    public function test_staff_can_take_breaks()
    {
        $user = User::factory()->create();


        $attendance =Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);


        $rests = Rest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'break_start_time' => now(),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('休憩戻')
            ->assertSee('休憩中');
    }

    // 休憩入は一日に複数回可能
    public function test_staff_can_take_multiple_break_start()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $rests = Rest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'break_start_time' => now(),
            'break_end_time' => now(),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('休憩入');

    }

    // 休憩戻ボタンの機能確認
    public function test_staff_end_the_breaks()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'date' => now()->toDateString(),
            'break_start_time' => now(),
        ]);

        $this->get('/attendance')
            ->assertSee('休憩戻');

        $response = $this->post(route('attendance.resume'));

        $response = $this->get('/attendance');
        $response->assertSee('勤務中');

    }

    // 休憩戻は一日に複数回可能
    public function test_staff_can_take_multiple_break_end()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        // 一回目の休憩
        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'date' => now()->toDateString(),
            'break_start_time' => now(),
            'break_end_time' => now(),
        ]);

        // 二回目の休憩（入りのみ）
        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'date' => now()->toDateString(),
            'break_start_time' => now(),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('休憩入');

    }

    // 退勤機能
    public function test_staff_end_working()
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('退勤');

        $this->post(route('attendance.end'));
        $this->get('/attendance')->assertSee('退勤済');

    }
}
