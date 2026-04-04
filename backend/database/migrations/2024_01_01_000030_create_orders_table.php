<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan_name', 50)->comment('套餐名称');
            $table->unsignedInteger('months')->comment('月数');
            $table->decimal('amount', 10, 2);
            $table->tinyInteger('status')->default(0)->comment('0:待支付 1:已支付 2:已取消');
            $table->string('payment_method', 30)->nullable()->comment('wechat/alipay');
            $table->string('transaction_id', 128)->nullable()->comment('第三方交易号');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
