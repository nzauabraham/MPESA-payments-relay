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
        Schema::create('shortcode_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('shortcode')->nullable();
            $table->string('parent_till')->nullable()->default(NULL);
            $table->longText('key')->nullable();
            $table->longText('secret')->nullable();
            $table->longText('passkey')->nullable();
            $table->longText('initiator')->nullable();
            $table->longText('initiator_password')->nullable();
            $table->decimal('charge',8,2)->default(0);
            $table->enum('charge_type',['value','percentage'])->nullable();
            $table->enum('type',['C2BPAYBILL','C2BBUYGOODS','B2C'])->default('C2B')->nullable();
            $table->enum('status',['active','inactive'])->nullable();
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
        Schema::dropIfExists('shortcode_configs');
    }
};
