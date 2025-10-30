<?php

namespace App\Http\Controllers;

use App\Models\Audits;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function getHistory($id, $type)
    {
        // Get audit records for the specified model
        $audits = Audits::where('model_id', $id)
            ->where('model_type', $type)
            ->with(['user:id,firstname,email']) // Eager load user data
            ->orderBy('edited_at', 'desc')
            ->get();

        // Format the response
        $history = $audits->map(function ($audit) {
            $user = $audit->user;
            $userFirstname = $user ? $user->firstname : 'Unknown User';
            $userEmail = $user ? $user->email : 'unknown@email.com';
            
            // Build the text message
            $mediumText = $audit->medium ? " melalui {$audit->medium}" : '';
            $text = "{$userFirstname} mengubah {$audit->column_name} dari {$audit->old_value} jadi {$audit->new_value}{$mediumText}";
            
            return [
                'text' => $text,
                'user_firstname' => $userFirstname,
                'user_email' => $userEmail,
                'column_name' => $audit->column_name,
                'old_value' => $audit->old_value,
                'new_value' => $audit->new_value,
                'medium' => $audit->medium,
                'edited_at' => $audit->edited_at,
            ];
        });

        return response()->json($history);
    }
}
