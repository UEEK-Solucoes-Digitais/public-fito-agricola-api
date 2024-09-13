<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function list($admin_id)
    {
        try {
            // $notifications = Notification::with('admin_responsable')->where('admin_id', $admin_id)->orderBy('created_at', 'desc')->get();

            // agrupando notificações por data, e agrupando pela lavoura 
            $notifications = Notification::with('admin_responsable')
                ->where('admin_id', $admin_id)
                ->where("type", "!=", "contents")
                ->whereHas('property_crop', function ($q) {
                    $q->where('status', 1);
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy(function ($item) {
                    return $item->created_at->format('d/m/Y');
                })
                ->map(function ($dateGroup) {
                    return $dateGroup->groupBy(function ($item) {

                        $name = $item->property_crop->crop->name;
                        unset($item->property_crop);
                        return $name; // Agrupando pelo nome da lavoura
                    });
                });

            // setando content_type das notifications com type = contents
            // $notifications->map(function ($dateGroup) {
            //     $dateGroup->map(function ($cropGroup) {
            //         $cropGroup->map(function ($notification) {
            //             if ($notification->type == 'contents') {
            //                 $notification->content_type = "{$notification->content->content_type}";
            //             }
            //         });
            //     });
            // });

            $notifications_contents = Notification::with(['admin_responsable', 'content.admin'])
                ->where('admin_id', $admin_id)
                ->where("type", "contents")
                ->whereHas('content', function ($q) {
                    $q->where('status', 1);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $notifications_contents->map(function ($notification) {
                $notification->content_type = "{$notification->content->content_type}";
            });

            return response()->json([
                'status' => 200,
                'notifications' => $notifications,
                'notifications_contents' => $notifications_contents,
                'length' => count($notifications)
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getCode() == 500 ? "Não foi possível realizar a operação no momento" : $e->getMessage(),
            ], $e->getCode());
        }
    }
}
