<?php
 
namespace App\Traits;
 
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
 
trait ApiResponse
{
    /**
     * Thành công (200 OK)
     */
    protected function ok($data = [], string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], Response::HTTP_OK);
    }
 
    /**
     * Tạo mới thành công (201 Created)
     */
    protected function created($data = [], string $message = 'Created successfully'): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], Response::HTTP_CREATED);
    }
 
    /**
     * Lỗi phía Client (400 Bad Request)
     */
    protected function error(string $message = 'Error', int $code = Response::HTTP_BAD_REQUEST, $errors = null): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }
 
    /**
     * Lỗi xác thực (401 Unauthorized)
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }
 
    /**
     * Lỗi quyền truy cập (403 Forbidden)
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }
 
    /**
     * Không tìm thấy (404 Not Found)
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }
}
