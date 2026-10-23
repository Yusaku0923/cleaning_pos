<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** ブラウザの JS エラー (旧 client_errors)。旧のデータは移さない。店舗で絞らない。 */
class ClientError extends Model
{
    protected $table = 'client_errors';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'extra_data' => 'array',
        ];
    }
}
