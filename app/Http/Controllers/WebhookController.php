<?php

namespace App\Http\Controllers;

use App\Models\MachineAttendance;
use App\Models\User;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handleAttendance(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $jsonContent = file_get_contents($file->getRealPath());
            $attendanceData = json_decode($jsonContent, true);
            foreach ($attendanceData as $attendance) {
                info($attendance);

                // Find the user by machine_id
                $user = User::where('machine_id', $attendance['id'])->first();
                info($user);

                if ($user) {
                    // Check if the attendance UID already exists
                    $exists = MachineAttendance::where('uid', $attendance['uid'])->exists();

                    // ! TODO: no need to check that at production
                    if ($exists) {
                        info("Attendance with UID {$attendance['uid']} already exists. Skipping.");
                        continue; // Skip to next attendance
                    }

                    try {
                        MachineAttendance::create([
                            'uid' => $attendance['uid'],
                            'attendance_id' => $attendance['id'],
                            'user_id' => $user->id,
                            'type' => $attendance['state'] == 1 ? 'checkin' : 'checkout',
                            'datetime' => $attendance['timestamp'],
                        ]);
                    } catch (\Throwable $th) {
                        info("Error creating attendance for user ID {$user->id}: " . $th->getMessage());
                    }
                }
            }
        }

        return response()->json(['message' => 'Attendance recorded successfully'], 200);
    }
}