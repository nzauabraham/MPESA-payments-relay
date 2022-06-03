<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('b2c_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('msisdn')->nullable();
            $table->decimal('charge',8,2)->default(0);
            $table->Integer('amount')->nullable();
            $table->string('unique_id')->nullable();
            $table->string('mpesa_reference')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('source')->nullable();
            $table->string('mpesa_response')->nullable();
            $table->string('success')->nullable();
            $table->string('reason')->nullable();
            $table->string('callback_url')->nullable();
            $table->json('mpesa_callback')->nullable();
            $table->foreignId('client_id')->nullable()->references('id')->on('customers')->nullOnDelete();
            $table->foreignId('channel_id')->nullable()->references('id')->on('channels')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('b2c_requests');
    }
};
