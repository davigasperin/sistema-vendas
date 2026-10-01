<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiController extends Controller
{
    /**
     * @param  class-string<Model>  $model
     * @param  class-string<JsonResource>  $resource
     */
    public function __construct(
        protected string $model,
        protected string $resource
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->model::query();

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where('name', 'like', "%{$term}%");
        }

        if ($request->filled('sort')) {
            $sort = in_array($request->input('sort'), ['id', 'name', 'created_at'], true)
                ? $request->input('sort')
                : 'id';

            $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sort, $direction);
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $this->resource::collection($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());
        /** @var Model $model */
        $model = $this->model::create($validated);

        return (new $this->resource($model))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function show(int $id): JsonResponse
    {
        $model = $this->model::findOrFail($id);

        return (new $this->resource($model))->response();
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->model::findOrFail($id);
        $validated = $request->validate($this->rules($id));
        $model->update($validated);

        return (new $this->resource($model->fresh()))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $model = $this->model::findOrFail($id);
        $model->delete();

        return response()->json([
            'data' => null,
            'message' => 'Registro excluído com sucesso.',
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function rules(?int $id = null): array
    {
        return [];
    }
}
