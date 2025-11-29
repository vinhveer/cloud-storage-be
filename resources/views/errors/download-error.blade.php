<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi tải file</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 max-w-lg w-full text-center">
        <div class="mb-6">
            <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <h1 class="text-2xl md:text-3xl font-semibold text-gray-800 mb-3">
            Không thể tải file
        </h1>
        <p class="text-gray-600 mb-6 leading-relaxed">
            {{ $message ?? 'Đã xảy ra lỗi khi tải file. Vui lòng thử lại sau.' }}
        </p>
        <div class="space-y-3">
            <a href="https://cloudfe.nguyenquangvinh.id.vn" class="inline-block w-full px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                Về trang chủ
            </a>
            <button onclick="window.history.back()" class="inline-block w-full px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors duration-200">
                Quay lại
            </button>
        </div>
    </div>
</body>
</html>

