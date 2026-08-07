<?php

namespace App\Models;

use CodeIgniter\Model;

class ClientImportBatchModel extends Model
{
    protected $table            = 'client_import_batches';
    protected $primaryKey       = 'import_batch_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'branch_id',
        'uploaded_by',
        'filename',
        'original_name',
        'mime_type',
        'file_path',
        'file_size',
        'format',
        'parse_status',
        'status',
        'total_records',
        'ready_count',
        'needs_attention_count',
        'duplicate_count',
        'skipped_count',
        'committed_count',
        'raw_text',
        'summary_json',
        'parse_error',
        'committed_at',
        'committed_by',
    ];
}
