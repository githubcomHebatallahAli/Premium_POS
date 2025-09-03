<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;



trait ManagesModelsTrait
{
    public function destroyModel(string $modelClass, string $resourceClass, string $id){
        $this->authorize('manage_users');
        $modelInstance = $modelClass::find($id);
        if (!$modelInstance) {
            return response()->json([
                'message' => class_basename($modelClass) . " not found."
            ], 404);
        }

        $modelInstance->delete();
        return response()->json([
            'data' => new $resourceClass($modelInstance),
            'message' => "Soft Delete " . class_basename($modelClass) . " By Id Successfully."
        ]);
    }

    public function showDeletedModels(string $modelClass, string $resourceClass){
        $this->authorize('manage_users');
        $models = $modelClass::onlyTrashed()->get();
        return response()->json([
            'data' => $resourceClass::collection($models),
            'message' => "Show Deleted " . class_basename($modelClass) . " Successfully."
        ]);
    }

    public function restoreModel(string $modelClass, string $id){
        $this->authorize('manage_users');
        $modelInstance = $modelClass::withTrashed()->where('id', $id)->first();
        if (!$modelInstance) {
            return response()->json([
                'message' => class_basename($modelClass) . " not found."
            ], 404);
        }

        $modelInstance->restore();
        return response()->json([
            'message' => "Restore " . class_basename($modelClass) . " By Id Successfully."
        ]);
    }



    public function forceDeleteModel(string $modelClass, string $id)
    {
        $this->authorize('manage_users');

        Log::info('Reached forceDeleteModel method for model: ' . $modelClass . ' with ID: ' . $id);

        $modelInstance = $modelClass::withTrashed()->where('id', $id)->first();

        if (!$modelInstance) {
            Log::info('Model not found: ' . $id);
            return response()->json([
                'message' => class_basename($modelClass) . " not found."
            ], 404);
        }

        Log::info('Model found, attempting to force delete: ' . $id);

        try {
            $modelInstance->forceDelete();
            Log::info('Force delete called for model: ' . $id);

            // Check if the model still exists
            $stillExists = $modelClass::withTrashed()->where('id', $id)->exists();
            if ($stillExists) {
                Log::info('Model still exists after force delete attempt: ' . $id);
                return response()->json([
                    'message' => 'Failed to force delete ' . class_basename($modelClass),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error during force delete: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }

        Log::info('Force delete successful for model: ' . $id);

        return response()->json([
            'message' => "Force Delete " . class_basename($modelClass) . " By Id Successfully."
        ]);
    }
}
