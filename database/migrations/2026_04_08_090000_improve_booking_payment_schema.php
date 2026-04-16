<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_provider', 30)->nullable()->after('order_code');
            $table->string('payment_status', 30)->default('created')->after('trang_thai');
            $table->text('checkout_url')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('checkout_url');
            $table->timestamp('cancelled_at')->nullable()->after('paid_at');
            $table->timestamp('expired_at')->nullable()->after('cancelled_at');

            $table->index(['customer_id', 'trang_thai']);
            $table->index(['suat_chieu_id', 'trang_thai']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('order_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('hoa_don_id');
            $table->index(['khach_hang_id', 'trang_thai']);
        });

        Schema::table('invoice_details', function (Blueprint $table) {
            $table->string('ten_san_pham', 255)->nullable()->after('san_pham_id');
        });

        Schema::table('customer_promotion', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('booking_id')->constrained('orders')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->after('order_id')->constrained('invoices')->nullOnDelete();
            $table->decimal('gia_tri_giam', 10, 2)->default(0)->after('so_lan_da_dung');

            $table->unique(['customer_id', 'promotion_id']);
            $table->index(['order_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_promotion', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'promotion_id']);
            $table->dropIndex(['order_id', 'invoice_id']);
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn('gia_tri_giam');
        });

        Schema::table('invoice_details', function (Blueprint $table) {
            $table->dropColumn('ten_san_pham');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['hoa_don_id']);
            $table->dropIndex(['khach_hang_id', 'trang_thai']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'trang_thai']);
            $table->dropIndex(['suat_chieu_id', 'trang_thai']);
            $table->dropColumn([
                'payment_provider',
                'payment_status',
                'checkout_url',
                'paid_at',
                'cancelled_at',
                'expired_at',
            ]);
        });
    }
};
