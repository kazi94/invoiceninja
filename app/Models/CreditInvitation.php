<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Models;

use App\Events\Credit\CreditWasUpdated;
use App\Jobs\Credit\CreateCreditPdf;
use App\Models\Invoice;
use App\Utils\Ninja;
use App\Utils\Traits\Inviteable;
use App\Utils\Traits\MakesDates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CreditInvitation extends BaseModel
{
    use MakesDates;
    use SoftDeletes;
    use Inviteable;

    protected $fillable = [
        'id',
        'client_contact_id',
    ];

    protected $with = [
        'company',
        'contact',
    ];

    protected $touches = ['credit'];

    public function getEntityType()
    {
        return CreditInvitation::class;
    }

    // public function getSignatureDateAttribute($value)
    // {
    //     if (!$value) {
    //         return (new Carbon($value))->format('Y-m-d');
    //     }
    //     return $value;
    // }

    // public function getSentDateAttribute($value)
    // {
    //     if (!$value) {
    //         return (new Carbon($value))->format('Y-m-d');
    //     }
    //     return $value;
    // }

    // public function getViewedDateAttribute($value)
    // {
    //     if (!$value) {
    //         return (new Carbon($value))->format('Y-m-d');
    //     }
    //     return $value;
    // }

    // public function getOpenedDateAttribute($value)
    // {
    //     if (!$value) {
    //         return (new Carbon($value))->format('Y-m-d');
    //     }
    //     return $value;
    // }
    
    public function entityType()
    {
        return Credit::class;
    }

    /**
     * @return mixed
     */
    public function credit()
    {
        return $this->belongsTo(Credit::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function contact()
    {
        return $this->belongsTo(ClientContact::class, 'client_contact_id', 'id')->withTrashed();
    }

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getName()
    {
        return $this->key;
    }

    public function markViewed()
    {
        $this->viewed_date = Carbon::now();
        $this->save();
    }

    public function pdf_file_path()
    {
        $storage_path = Storage::url($this->credit->client->quote_filepath() . $this->credit->number . '.pdf');

        if (!Storage::exists($this->credit->client->credit_filepath() . $this->credit->number . '.pdf')) {
            event(new CreditWasUpdated($this, $this->company, Ninja::eventVars()));
            CreateCreditPdf::dispatchNow($this);
        }

        return $storage_path;
    }
}
