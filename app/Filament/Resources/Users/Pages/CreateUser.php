<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $v = Validator::make(
            $data,
            ['password' => ['required', 'string', 'min:8']]
        );

        if ($v->fails()) {
            throw ValidationException::withMessages($v->errors()->messages());
        }

        return $data;
    }
}
