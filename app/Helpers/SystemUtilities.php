<?php

use App\Exceptions\OperationException;
use App\Http\Controllers\Api\V1\FirebaseHelperController;
use App\Models\Admin;
use App\Models\LogError;
use App\Models\LogSystem;
use App\Models\Notification;
use Illuminate\Support\Carbon;

function checkSection($admin_id)
{
    $session_item = config('app.session_name') . "_admin_id";

    if (!session()->has($session_item) || (session()->has($session_item) && session($session_item) != $admin_id)) {
        session([$session_item => $admin_id]);
    }
}

function createLogSystem($id, $table_name, $type, $from = [], $dirty = [])
{
    unset($from['draw_area']);

    unset($dirty['draw_area']);

    LogSystem::create([
        'admin_id' => session(config('app.session_name') . "_admin_id"),
        'table_name' => $table_name,
        'operation' => $type,
        'object_id' => $id,
        'from' => count($from) > 0 ? json_encode($from) : "",
        'to' => count($dirty) > 0 ? json_encode($dirty) : "",
    ]);
}


function getTextStatus($status, $end = 0, $type = 1)
{
    switch ($status) {
        case 0:
            return "removid" . ($end == 1 ? "a" : "o");
        case 1:
            return $type  == 1 ? "ativad" . ($end == 1 ? "a" : "o") : "aprovad" . ($end == 1 ? "a" : "o");
        case 2:
            return "inativad" . ($end == 1 ? "a" : "o");
        case 3:
            return "reprovad" . ($end == 1 ? "a" : "o");
    }
}

function getStageText($stage)
{
    $text = "";

    if ($stage->vegetative_age_value == 0 && $stage->reprodutive_age_value == 0) {
        $text .= "V0 - ";
    } else {
        if ($stage->vegetative_age_value > 0) {
            $text .= "V" . str_replace(".0", "", $stage->vegetative_age_value) . " - ";
        }
        if ($stage->reprodutive_age_value > 0) {
            $text .= "R" . str_replace(".0", "", $stage->reprodutive_age_value) . " - ";
        }
    }

    $text .= Carbon::createFromFormat('Y-m-d', $stage->open_date)->format("d/m/Y");

    return $text;
}

function friendlyUrl($var)
{
    try {
        // Retira tudo que não for letra e número
        $url = preg_replace('/[^\p{L}\p{N}\s]/', '', $var);

        // Retira acentos
        $url = strtolower(preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $var));

        $url = strtolower(preg_replace("[^a-zA-Z0-9-]", "-", strtr(utf8_decode(trim($url)), utf8_decode("áàãâéêíóôõúüñçÁÀÃÂÉÊÍÓÔÕÚÜÑÇ"), "aaaaeeiooouuncAAAAEEIOOOUUNC-")));

        // Adiciona hifen nos espaços
        $url = preg_replace('/[ -]+/', '-', $url);

        $url = str_replace(".", "", $url);
        $url = str_replace("ç", "c", $url);
        $url = str_replace("Ç", "C", $url);

        $url = preg_replace('/[^\w]+/', '-', $url);

        // Retorna
        return $url;
    } catch (Exception $e) {
        error_log($e->getMessage(), 0);
        return $var;
    }
}

function isBase64($string)
{
    return strlen($string) > 50;
    // $base64StartPos = strpos($string, 'base64,') + 7;

    // if ($base64StartPos === false || $base64StartPos >= strlen($string)) {
    //     return false;
    // }

    // $base64 = substr($string, $base64StartPos);

    // $teste = preg_match('/^(?:[A-Za-z0-9+\/]{4})*?(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/', $base64);

    // if (!$teste) {
    //     throw new OperationException('Base 64 retornou falso', '', "", 409);
    // }

    // return $teste;
}

function getRisk($risk)
{
    switch ($risk) {
        case 1:
            return "Sem risco";
            break;
        case 2:
            return "Atenção";
            break;
        case 3:
            return "Urgência";
            break;
    }
}

function getColor($risk)
{
    switch ($risk) {
        case 1:
            return "green";
            break;
        case 2:
            return "yellow";
            break;
        case 3:
            return "red";
            break;
    }
}

function getObjectType($type)
{
    switch ($type) {
        case 1:
            return 'Adjuvante';
        case 2:
            return 'Biológico';
        case 3:
            return 'Fertilizante foliar';
        case 4:
            return 'Fungicida';
        case 5:
            return 'Herbicida';
        case 6:
            return 'Inseticida';
        case 7:
            return 'Regulador de crescimento';
        case 8:
            return 'Indutor';
        default:
            return 'Adjuvante';
    }
}
function getAlternativeType($type)
{
    switch ($type) {
        case 1:
            return 'Imposto';
        case 2:
            return 'Manutenção';
        case 3:
            return 'Seguro';
        case 4:
            return 'Combustível';
        case 5:
            return 'Colaborador';
        case 6:
            return 'Item';
        default:
            return 'Outros';
    }
}

function createNotification($title, $text, $level, $admin_id, $object_id, $type = "monitoring", $add_extras = '', $content_interaction = 0, $content_text = '', $subtype = null)
{
    try {
        // verificando se não há uma notificação igual essa nos ultimos 30 segudos para o admin_id
        $last_notification = Notification::where('admin_id', $admin_id)
            ->where('object_id', $object_id)
            ->where('title', $title)
            ->where('created_at', '>=', Carbon::now()->subSeconds(60))
            ->first();

        if (!$last_notification) {

            $admin_session = session(config('app.session_name') . "_admin_id");

            $notification = new Notification();
            $notification->title = $title;
            $notification->text = $text;
            $notification->level = $level;
            $notification->admin_id = $admin_id;
            $notification->object_id = $object_id;
            $notification->created_by_admin_id = $admin_session;
            $notification->type = $type;
            $notification->subtype = $subtype;
            $notification->content_interaction = $content_interaction;
            $notification->content_text = $content_text;
            $notification->is_read = $type == "contents" ? 0 : 1;
            $notification->save();

            // envio da notificação push
            $admin = Admin::find($admin_id);

            foreach ($admin->tokens as $token) {
                FirebaseHelperController::SendGenericNotification($token->token, $title, $text, "object_id=$object_id{$add_extras}&type=$type");
            }
        }
    } catch (OperationException $e) {
        report($e);
    }
}


function isString($variable)
{
    if (stripos($variable, ',') !== false) {
        return str_replace(",", ".", str_replace(".", "", $variable));
    } else {
        return $variable;
    }
}
