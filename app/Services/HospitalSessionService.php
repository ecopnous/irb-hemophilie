<?php

namespace App\Services;

use App\Models\Configs\Hopital;
use App\Models\User;
use Illuminate\Http\Request;

class HospitalSessionService
{
    public function sync(Request $request, ?User $user): void
    {
        if (!$user) {
            $this->clear($request);

            return;
        }

        $selectedHospitalId = $request->session()->get('hopital_id');
        $selectedHospitalUserId = $request->session()->get('hopital_session_user_id');

        if ($selectedHospitalId && (int) $selectedHospitalUserId === (int) $user->getAuthIdentifier()) {
            $selectedHospital = Hopital::query()
                ->select(['id', 'name', 'reference', 'type', 'devise', 'code_postal'])
                ->find($selectedHospitalId);

            if ($selectedHospital) {
                $this->setCurrent($request, $selectedHospital);

                return;
            }
        }

        $user->loadMissing('hopital:id,name,reference,type,devise,code_postal');

        if ($user->hopital) {
            $this->setCurrent($request, $user->hopital);

            return;
        }

        $this->clear($request);
    }

    public function setCurrent(Request $request, Hopital $hopital): void
    {
        $request->session()->put([
            'hopital' => [
                'id' => $hopital->id,
                'name' => $hopital->name,
                'reference' => $hopital->reference,
                'type' => $hopital->type,
                'devise' => $hopital->devise,
                'code_postal' => $hopital->code_postal,
            ],
            'hopital_id' => $hopital->id,
            'hopital_nom' => $hopital->name,
            'hopital_session_user_id' => $request->user()?->getAuthIdentifier(),
        ]);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget([
            'hopital',
            'hopital_id',
            'hopital_nom',
            'hopital_session_user_id',
        ]);
    }
}
