<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OperationException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminContentInteractions;
use App\Models\AdminWatchedContent;
use App\Models\Content;
use App\Models\ContentBlock;
use App\Models\ContentBlockGallery;
use App\Models\ContentCategory;
use App\Models\ContentComment;
use App\Models\ContentCommentLike;
use App\Models\ContentTypeAccess;
use App\Models\ContentVideo;
use App\Models\Notification;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContentController extends Controller
{
    /**
     * Lista as conteúdos de um admin
     * @param admin_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function list(Request $request, $admin_id, $content_type)
    {
        try {
            $filter = $request->get("filter") ?? "";
            $page = $request->get("page") ?? "";

            list($contents, $total) = Content::readContents($filter, $page, $admin_id, $content_type);


            $admin = Admin::find($admin_id);

            // $admins_ids = explode(',', join(',', $contents->where('admins_ids', '!=', null)->pluck('admins_ids')->toArray()));

            // if ($content_type != 1 && in_array($admin->id, $admins_ids) && !ContentTypeAccess::where('type', $content_type)->where('admin_id', $admin_id)->first()) {
            //     ContentTypeAccess::create([
            //         'type' => $content_type,
            //         'admin_id' => $admin_id
            //     ]);
            // } else if (in_array($admin->id, $admins_ids) && stripos($admin->level, 'contents') === false) {
            //     $admin->level = $admin->level . ',contents';
            //     $admin->save();
            // }

            if ($admin->access_level != 1) {
                $actual_contents = $contents->pluck('id')->toArray();

                list($unavailable_contents, $total_courses) = Content::readContents($filter, $page, $admin_id, $content_type, $actual_contents, true);

                $contents = $contents->merge($unavailable_contents->values());
                $courses = $contents->where('is_course', 1)->values();
            }

            $courses = $contents->where('is_course', 1)->sortBy('position')->values();

            if ($content_type != 1 && $admin->access_level != 1 && !ContentTypeAccess::where('type', $content_type)->where('admin_id', $admin_id)->first()) {
                $contents->map(function ($content) {
                    // $content->is_saved = 0;
                    $content->is_available = 0;
                    return $content;
                });
            }

            $content_categories = ContentCategory::readCategoriesByPosition();
            $most_viewed = $contents->where('count_finished', '>', 0)->sortByDesc('count_finished')->take(5)->values();

            // dd($contents->sortByDesc('count_finished')->values()->toArray());

            return response()->json([
                'status' => 200,
                'courses' => $courses,
                'contents' => $contents->where('is_course', 0)->values(),
                'saved_contents' => $contents->where('is_saved', 1)->values(),
                'keep_watching' => $contents->where('is_watching', 1)->values(),
                'most_viewed' => $most_viewed,
                'all_contents' => $contents,
                'content_categories' => $content_categories,
                'newest' => $contents->take(30)->values(),
                'total' => $total,
                'access_enabled' => true,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * lê um conteúdo
     * @param id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function read($admin_id, $url, Request $request)
    {
        try {
            $content = Content::readContent($url, $admin_id);

            // $watched = AdminWatchedContent::where('admin_id', $admin_id)->where('content_id', $content->id)->first();

            // if (!$watched) {
            //     checkSection($admin_id);
            //     $watched = new AdminContentInteractions();
            //     $watched->admin_id = $admin_id;
            //     $watched->content_id = $content->id;
            //     $watched->save();
            // }
            $content_categories = ContentCategory::readCategories();

            if ($request->get("is_from_mobile")) {
                Notification::where('admin_id', $admin_id)->where('object_id', $content->id)->where('type', 'contents')->update(['is_read' => 1]);
            }

            return response()->json([
                'status' => 200,
                'content' => $content,
                'content_categories' => $content_categories,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function readItemsForm($admin_id)
    {
        try {
            $admin = Admin::where('id', $admin_id)->where('status', '!=', 0)->first();
            list($properties, $total) = Property::readProperties($admin_id, []);
            $admins = Admin::where('status', '!=', 0)->orderBy('name', 'ASC');

            $admins = $admins->get();

            if ($admin && $admin->access_level != 1) {
                $admins = collect([$admin]);
            }

            $cities = collect([]);
            $countries = collect([]);
            $states = collect([]);

            foreach ($admins as $admin) {
                if ($admin->city && in_array($admin->city, $cities->pluck('name')->toArray()) == false) {
                    $cities->add((object)[
                        'id' => $admin->city,
                        'name' => $admin->city,
                    ]);
                }
                if ($admin->country && in_array($admin->country, $countries->pluck('name')->toArray()) == false) {
                    $countries->add((object)[
                        'id' => $admin->country,
                        'name' => $admin->country,
                    ]);
                }
                if ($admin->state && in_array($admin->state, $states->pluck('name')->toArray()) == false) {
                    $states->add((object)[
                        'id' => $admin->state,
                        'name' => $admin->state,
                    ]);
                }
            }

            // sort states, cities and countries
            $cities = $cities->sortBy('name')->values();
            $countries = $countries->sortBy('name')->values();
            $states = $states->sortBy('name')->values();

            // dd($cities);

            return response()->json([
                'status' => 200,
                'properties' => $properties,
                'admins' => $admins,
                'cities' => $cities,
                'countries' => $countries,
                'states' => $states,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formulário de criação/edição de publicação
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function form(Request $request)
    {
        // return response()->json([
        //     'status' => 500,
        //     'content' => $request->all(),
        // ], 500);

        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'title' => 'required',
                'categories_ids' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar Conteúdo', Content::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 409);
            }

            checkSection($request->admin_id);



            if ($request->id) {
                $content = Content::find($request->id);

                if (!$content) {
                    throw new OperationException('Erro ao ler conteúdo na operação de edição', Content::getTableName(), "Conteúdo não encontrado: {$request->id}", 409);
                }
            } else {
                $content = new Content();
            }

            $content->title = $request->title;
            $content->url = '';
            $content->categories_ids = $request->categories_ids;
            $content->content_type = $request->content_type;
            $content->text = $request->text ?? '';
            $content->position = $request->position ?? 999;

            if (!$request->id) {
                $content->admin_id = $request->admin_id;
            }

            $content->date = date('Y-m-d');
            $content->image = $request->image ?? ($request->id ? $content->image : '');
            $content->course_cover = $request->course_cover ?? ($request->id ? $content->course_cover : '');
            $content->most_watched_cover = $request->most_watched_cover ?? ($request->id ? $content->most_watched_cover : '');
            $content->save();
            $content->url = friendlyUrl($request->title) . "-" . $content->id;
            $content->is_course = $request->is_course ?? 0;
            // $content->duration_time = $request->duration_time ?? '';
            $content->highlight_category_id = $request->highlight_category_id ?? null;
            $content->cities = $request->cities ?? null;
            $content->countries = $request->countries ?? null;
            $content->states = $request->states ?? null;
            $content->access_level = $request->access_level ?? null;
            $content->admins_ids = $request->admins_ids ?? null;
            $content->properties_ids = $request->properties_ids ?? null;
            $content->save();

            if ($request->admins_ids) {
                foreach (explode(",", $request->admins_ids) as $admin_id) {
                    $admin = Admin::find($admin_id);

                    if ($admin->access_level != 1) {

                        if ($content->content_type != 1 && ContentTypeAccess::where('type', $content->content_type)->where('admin_id', $admin_id)->count() == 0) {
                            $access = new ContentTypeAccess();
                            $access->type = $content->content_type;
                            $access->admin_id = $admin_id;
                            $access->save();
                        } else if (in_array($admin->id, explode(",", $request->admins_ids)) && stripos($admin->level, 'contents') === false) {
                            $admin->level = $admin->level . ',contents';
                            $admin->save();
                        }
                    }
                }
            }

            if (isset($request->videos) && $request->videos) {
                foreach ($request->videos as $video) {
                    $video = (object) $video;
                    $duration_split = explode(":", $video->duration_time);
                    if (
                        count($duration_split) != 3 ||
                        (isset($duration_split[0]) && intval($duration_split[0]) < 1 && isset($duration_split[1]) && intval($duration_split[1]) < 1 && isset($duration_split[2]) && intval($duration_split[2]) < 1)
                    ) {
                        throw new OperationException('Erro ao cadastrar/editar Conteúdo', Content::getTableName(), "Insira uma duração de no mínimo 1 minuto no formato 00:00:00 (hora, minuto e segundo)", 409);
                    }
                }
                $videos_not_delete = [];
                foreach ($request->videos as $video) {
                    if ($video) {
                        $video = (object) $video;

                        if ($video->id) {
                            $content_video = ContentVideo::find($video->id);
                        } else {
                            $content_video = new ContentVideo();
                        }
                        $content_video->content_id = $content->id;
                        $content_video->title = $video->title ?? '';
                        $content_video->description = $video->description ?? '';
                        $content_video->video_link = $video->video_link;
                        $content_video->duration_time = $video->duration_time;
                        $content_video->save();

                        $videos_not_delete[] = $content_video->id;
                    }
                }
                ContentVideo::where('content_id', $content->id)->whereNotIn('id', $videos_not_delete)->update(['status' => 0]);
            }

            if (isset($request->blocks) && $request->blocks) {
                $blocks_not_delete = [];
                foreach ($request->blocks as $key => $block) {
                    $block = (object) $block;

                    if (isset($block->id) && $block->id && $block->id != 0) {
                        $content_block = ContentBlock::find($block->id);
                    } else {
                        $content_block = new ContentBlock();
                        $content_block->content_id = $content->id;
                        $content_block->type = $block->type;
                    }

                    $content_block->content = $block->content ?? "";
                    $content_block->position = $key;
                    $content_block->status = 1;
                    $content_block->save();

                    $blocks_not_delete[] = $content_block->id;

                    if ($block->type == 3) {
                        if (isset($block->images) && $block->images) {
                            foreach ($block->images as $image) {
                                if ($image) {
                                    $content_block_gallery = new ContentBlockGallery();
                                    $content_block_gallery->content_block_id = $content_block->id;
                                    $content_block_gallery->image = $image;
                                    $content_block_gallery->save();
                                }
                            }
                        }
                    }
                }

                ContentBlock::where('content_id', $content->id)->whereNotIn('id', $blocks_not_delete)->update(['status' => 0]);
            }

            $text = $request->id ? "Conteúdo editado com sucesso" : "Conteúdo cadastrado com sucesso";

            return response()->json([
                'status' => 200,
                'msg' => $text,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Altera o status de um conteúdo
     * @param admin_id
     * @param id - id do conteúdo
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status do conteúdo', Content::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $content = Content::find($request->id);

            if (!$content) {
                throw new OperationException('Erro ao ler conteúdo na operação de alteração de status', Content::getTableName(), "Conteúdo não encontrado: {$request->id}", 409);
            }

            $content->status = 0;
            $content->save();

            return response()->json([
                'status' => 200,
                'msg' => "Conteúdo removido com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Deleta uma imagem da galeria
     * @param admin_id
     * @param id - id do conteúdo
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status da imagem do conteúdo', Content::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $content_image = ContentBlockGallery::find($request->id);

            if (!$content_image) {
                throw new OperationException('Erro ao ler imagem do conteúdo na operação de alteração de status', Content::getTableName(), "Imagem não encontrada: {$request->id}", 409);
            }

            $content_image->status = 0;
            $content_image->save();

            return response()->json([
                'status' => 200,
                'msg' => "Imagem removida com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Lista todas as categorias
     * @param null $id
     * @return \Illuminate\Http\JsonResponse
     */

    public function listCategory()
    {
        try {
            $categories = ContentCategory::readCategories();

            return response()->json([
                'status' => 200,
                'categories' => $categories,
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'msg' => 'Ocorreu um erro interno ao realizar a operação',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function formCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao cadastrar/editar Categoria em Conteudos', ContentCategory::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $category = ContentCategory::find($request->id);

                if (!$category) {
                    throw new OperationException('Erro ao ler categoria (conteúdos) na operação de edição', ContentCategory::getTableName(), "Categoria não encontrada: {$request->id}", 409);
                }
            } else {
                $category = new ContentCategory();
            }

            $category->name = $request->name;
            $category->save();

            return response()->json([
                'status' => 200,
                'msg' => "Categoria cadastrada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * Altera o status de uma categoria
     * @param admin_id
     * @param id - id da categoria
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status da categoria', ContentCategory::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $category = ContentCategory::find($request->id);

            if (!$category) {
                throw new OperationException('Erro ao ler categoria na operação de alteração de status', ContentCategory::getTableName(), "Categoria não encontrada: {$request->id}", 409);
            }

            if (Content::where('status', 1)->where('categories_ids', 'LIKE', "%{$category->id}%")->first()) {
                throw new OperationException('Erro ao excluir categoria', ContentCategory::getTableName(), "Categoria vinculada em conteúdo(s)", 409);
            }

            $category->status = 0;
            $category->save();

            return response()->json([
                'status' => 200,
                'msg' => "Categoria removida com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function organizeCategory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar status da categoria', ContentCategory::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            foreach ($request->categories as $key => $category_id) {
                $category = ContentCategory::find($category_id);
                $category->position = $key;
                $category->save();
            }

            return response()->json([
                'status' => 200,
                'msg' => "Categorias ordenadas com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * salvar interação de conteúdo
     * @param admin_id
     * @param content_id
     * @param interaction
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveInteraction(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'content_id' => 'required',
                'interaction' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar interação na publicação', ContentCategory::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $interaction = AdminContentInteractions::where('admin_id', $request->admin_id)->where('content_id', $request->content_id)->first();

            if (!$interaction) {
                $interaction = new AdminContentInteractions();
                $interaction->admin_id = $request->admin_id;
                $interaction->content_id = $request->content_id;
            }

            $interaction->{$request->interaction} = $interaction->{$request->interaction} == 1 ? 0 : 1;
            $interaction->save();

            return response()->json([
                'status' => 200,
                'msg' => "Interação feita com sucesso",
                "interaction" => $interaction
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * alterar visualização de conteúdo
     * @param admin_id
     * @param content_id
     * @param item
     * @param value
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateWatched(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'content_video_id' => 'required',
                'item' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar interação na publicação', ContentCategory::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $watched = AdminWatchedContent::where('admin_id', $request->admin_id)->where('content_video_id', $request->content_video_id)->first();

            if (!$watched) {
                $watched = new AdminWatchedContent();
                $watched->admin_id = $request->admin_id;
                $watched->content_video_id = $request->content_video_id;
            }

            $watched->{$request->item} = $request->value;

            if ($request->item == "last_second" && $watched->is_finished == 1) {
                $watched->is_finished = 0;
            }
            $watched->save();

            return response()->json([
                'status' => 200,
                'msg' => "Atualização feita com sucesso",
                "watched" => $watched
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * criar/editar comentário
     * @param admin_id
     * @param content_id
     * @param text
     * @param answer_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function formComment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'content_id' => 'required',
                'text' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar interação na publicação', ContentCategory::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            if ($request->id) {
                $comment = ContentComment::find($request->id);
            } else {
                $comment = new ContentComment();
                $comment->content_id = $request->content_id;
                $comment->admin_id = $request->admin_id;
            }

            $comment->answer_id = $request->answer_id ?? null;
            $comment->text = $request->text;
            $comment->save();


            $admins = Admin::where('id', '!=', $comment->admin_id)->where("status", 1)->where("access_level", 1);

            if (!$request->id && $request->answer_id) {
                // enviar notificação pro usuario que foi respondido
                $original_comment = ContentComment::find($request->answer_id);

                if ($original_comment && $original_comment->admin) {
                    $admins = $admins->where('id', '!=', $original_comment->admin->id);

                    $title = "Você recebeu uma resposta";
                    $message = "{$comment->admin->name} respondeu seu comentário no conteúdo {$original_comment->content->title}";
                    createNotification($title, $message, 0, $original_comment->admin->id, $original_comment->content->id, "contents", "&content_type={$original_comment->content->content_type}", 1, $comment->text);
                }
            }

            $admins = $admins->get();

            foreach ($admins as $admin) {
                $title = "Novo comentário";
                $message = "{$comment->admin->name} comentou no conteúdo {$comment->content->title}";
                createNotification($title, $message, 0, $admin->id, $comment->content->id, "contents", "&content_type={$comment->content->content_type}", 2, $comment->text);
            }


            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
                "comment" => ContentComment::find($comment->id)
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * curtir/descurtir comentário
     * @param admin_id
     * @param content_comment_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function likeComment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'content_comment_id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar interação na publicação', ContentCategory::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $like = ContentCommentLike::where('admin_id', $request->admin_id)->where('content_comment_id', $request->content_comment_id)->first();

            if (!$like) {
                $like = new ContentCommentLike();
                $like->admin_id = $request->admin_id;
                $like->content_comment_id = $request->content_comment_id;
                $like->save();

                // enviar notificação pro admin do comentário
                $comment = ContentComment::find($request->content_comment_id);

                if ($comment && $comment->admin) {
                    $title = "Novo like no seu comentário";
                    $message = "{$like->admin->name} curtiu seu comentário no conteúdo {$comment->content->title}";
                    createNotification($title, $message, 0, $comment->admin->id, $comment->content->id, "contents", "&content_type={$comment->content->content_type}", 3);
                }
            } else {
                $like->delete();
            }

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    /**
     * remover comentário
     * @param admin_id
     * @param comment_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeComment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar interação na publicação', ContentCategory::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            $comment = ContentComment::find($request->id);

            if (!$comment) {
                throw new OperationException('Erro ao ler comentário na operação de remoção', ContentComment::getTableName(), "Comentário não encontrado: {$request->id}", 409);
            }

            $comment->status = 0;
            $comment->save();

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }


    public function listAccessType($type)
    {
        try {
            $admins = Admin::where('status', 1)->where('access_level', '!=', 1)->orderBy('name', 'ASC')->get();
            $selected_admins = ContentTypeAccess::where('type', $type)->pluck('admin_id')->toArray();

            return response()->json([
                'status' => 200,
                'admins' => $admins,
                'selected_admins' => $selected_admins,
            ], 200);

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }
    /**
     * remover comentário
     * @param admin_ids
     * @param type
     * @return \Illuminate\Http\JsonResponse
     */
    public function formAccessType(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'admin_id' => 'required',
                'admin_ids' => 'required',
            ]);

            if ($validator->fails()) {
                throw new OperationException('Erro ao alterar interação na publicação', ContentCategory::getTableName(), "Campos faltando no request: <br> {$validator->errors()}", 422);
            }

            checkSection($request->admin_id);

            ContentTypeAccess::where('type', $request->type)->delete();

            foreach ($request->admin_ids as $admin_id) {
                $access = new ContentTypeAccess();
                $access->type = $request->type;
                $access->admin_id = $admin_id;
                $access->save();
            }

            return response()->json([
                'status' => 200,
                'msg' => "Operação realizada com sucesso",
            ], 200);
        } catch (OperationException $e) {
            report($e);

            return response()->json([
                'status' => $e->getCode(),
                'msg' => $e->getMessage(),
            ], $e->getCode());
        }
    }
}
