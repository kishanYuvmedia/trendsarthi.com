<?php

namespace App\Http\Controllers\Backend\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use View;
use DB;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Carbon\Carbon;

class UserDashboardController extends Controller
{
    public function index()
    {
        return view('backend.user.home');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('backend.user.profile', compact('user'));
    }

    public function edit()
    {
        $user = Auth::user();
        return view('backend.user.edit_profile', compact('user'));
    }

    public function update(Request $request)
    {
        if ($request->ajax()) {

            $user = User::findOrFail(Auth::user()->id);

            $rules = [
                'name' => 'required',
                'photo' => 'image|max:2024|mimes:jpeg,jpg,png'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'errors' => $validator->getMessageBag()->toArray()
                ]);
            } else {

                $file_path = $request->input('SelectedFileName');;

                if ($request->hasFile('photo')) {
                    if ($request->file('photo')->isValid()) {
                        $destinationPath = public_path('assets/images/users/');
                        $extension = $request->file('photo')->getClientOriginalExtension(); // getting image extension
                        $fileName = time() . '.' . $extension;
                        $file_path = 'assets/images/users/' . $fileName;
                        $request->file('photo')->move($destinationPath, $fileName);
                    } else {
                        return response()->json([
                            'type' => 'error',
                            'message' => "<div class='alert alert-warning'>Please! File is not valid</div>"
                        ]);
                    }
                }

                DB::beginTransaction();
                try {
                    $user->name = $request->input('name');
                    $user->file_path = $file_path;
                    $user->save();

                    DB::commit();
                    return response()->json(['type' => 'success', 'message' => "Successfully Updated"]);

                } catch (\Exception $e) {
                    DB::rollback();
                    return response()->json(['type' => 'error', 'message' => $e->getMessage()]);
                }

            }
        } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
        }
    }

    public function change_password()
    {
        return view('backend.user.change_password');
    }

    public function update_password(Request $request)
    {
        if ($request->ajax()) {

            $user = User::findOrFail(Auth::user()->id);

            $rules = [
                'password' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'type' => 'error',
                    'errors' => $validator->getMessageBag()->toArray()
                ]);
            } else {
                $user->password = Hash::make($request->input('password'));
                $user->save(); //
                return response()->json(['type' => 'success', 'message' => "Successfully Updated"]);
            }
        } else {
            return response()->json(['status' => 'false', 'message' => "Access only ajax request"]);
        }
    }
    public function Getdata($type)
   {
        $currentNftData = 'http://nimblerest.lisuns.com:4531/GetLastQuote/?accessKey=988dcf72-de6b-4637-9af7-fddbe9bfa7cd&exchange=NFO&instrumentIdentifier=' . $type . '-I';
        $currentNftDataresult = Http::get($currentNftData)->json();
        $currentOptionSrike = $currentNftDataresult['AVERAGETRADEDPRICE'];
        $expiryApiUrl = 'http://nimblerest.lisuns.com:4531/GetExpiryDates/?accessKey=988dcf72-de6b-4637-9af7-fddbe9bfa7cd&exchange=NFO&product=' . $type;
        $expApiResult = Http::get($expiryApiUrl)->json();
        $expDt = $expApiResult['EXPIRYDATES'] ?? [];
        $typeNft = $type;
        $currentTimestamp = time();
        $expArray = [];
        $selectedDate = null;
        foreach ($expDt as $option) {
            $carbonDate = Carbon::createFromFormat('dMY', $option);
            $timestamp = $carbonDate->timestamp;
            $isUpcomingOrCurrent = $timestamp >= $currentTimestamp;
            $isUpcomingAfterInitial = empty($selectedDate) && $isUpcomingOrCurrent;
            if ($isUpcomingAfterInitial) {
                $selectedDate = $option;
            }
            $expArray[] = [
                'option' => $option,
                'isUpcomingAfterInitial' => $isUpcomingAfterInitial,
            ];
        }
        
        $apiEndpoint = 'http://nimblerest.lisuns.com:4531/GetLastQuoteOptionChain/?accessKey=988dcf72-de6b-4637-9af7-fddbe9bfa7cd&exchange=NFO&product=' . $type . '&expiry=' . $selectedDate;
        try {
            $apiResult = Http::get($apiEndpoint)->json();
            $putArr = [];
            $callArr = [];
            $finalcallputvalue = [];
            foreach ($apiResult as $result) {
                $identi = explode('_', $result['INSTRUMENTIDENTIFIER']);
                $value = end($identi);
                if ($result['SERVERTIME'] > 0) {
                    if ($identi[3] == 'CE') {
                        $callArr[] = array_merge($result, ['value' => (int)$value,'optionType'=>$identi[3],'optionDate'=>$identi[2]]);
                    } elseif ($identi[3] == 'PE') {
                        $putArr[] = array_merge($result, ['value' => (int)$value,'optionType'=>$identi[3],'optionDate'=>$identi[2]]);
                    }
                }
            }
            $closest = 0;
            foreach ($callArr as $item) {
                if ($closest === null || abs($currentOptionSrike - $closest) > abs($item['value'] - $currentOptionSrike)) {
                    $closest = $item['value'];
                }
            }
            $index = -1;
            // Iterate through the array and search for the value
            foreach ($callArr as $key => $subArray) {
                if (in_array($closest, $subArray)) {
                    $index = $key;
                    break;
                }
            }
            if ($index !== -1) {
                $dataList = [];
                for ($i = $index - 6; $i < $index + 7; $i++) {
                    $dataList[] = [
                        'put' => $putArr[$i],
                        'call' => $callArr[$i],
                        'strike' => $closest,
                    ];
                }
               
                return view('backend.user.derivatives', compact('dataList', 'typeNft', 'currentNftDataresult'));
            } else {
                return view('backend.user.derivatives', compact('dataList', 'typeNft', 'currentNftDataresult'));
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
      
   }
   public function getOpenInterestChartData()
{
   // Prepare data (example data)
   $timeLabels = ['Session 1', 'Session 2', 'Session 3', 'Session 4', 'Session 5'];
   $amountData = [5000000, 15000000, 20000000, 21500000, 24000000];
   $zeroData = [0, 0, 0, 0, 0];
   return response()->json([
       'labels' => $timeLabels,
       'data' => $amountData,
       'zero'=>  $zeroData,
   ]);
}
public function getOpenInterestChartDatatwo()
{
   // Prepare data (example data)
   $timeLabels = ['Session 1', 'Session 2', 'Session 3', 'Session 4', 'Session 5'];
   $amountData = [5000000, 15000000, 20000000, 21500000, 24000000];
   $zeroData = [0, 0, 0, 0, 0];
   return response()->json([
       'labels' => $timeLabels,
       'data' => $amountData,
       'zero'=>  $zeroData,
   ]);
}
public function getcurrentstrike($type)
   {
     $currentNftData = 'http://nimblerest.lisuns.com:4531/GetLastQuote/?accessKey=988dcf72-de6b-4637-9af7-fddbe9bfa7cd&exchange=NFO&instrumentIdentifier=' . $type . '-I';
     $currentNftDataresult = Http::get($currentNftData)->json();
     return response()->json([$currentNftDataresult]);
   }
   public function getexpdate($type)
   {
       $expiryApiUrl = 'http://nimblerest.lisuns.com:4531/GetExpiryDates/?accessKey=988dcf72-de6b-4637-9af7-fddbe9bfa7cd&exchange=NFO&product=' . $type;
       $expApiResult = Http::get($expiryApiUrl)->json();
       $expDt = $expApiResult['EXPIRYDATES'] ?? [];
       $typeNft = $type;
       $currentTimestamp = time();
       $expArray = [];
       $selectedDate = null;
       foreach ($expDt as $option) {
           $carbonDate = Carbon::createFromFormat('dMY', $option);
           $timestamp = $carbonDate->timestamp;
           $isUpcomingOrCurrent = $timestamp >= $currentTimestamp;
           $isUpcomingAfterInitial = empty($selectedDate) && $isUpcomingOrCurrent;
           if ($isUpcomingAfterInitial) {
               $selectedDate = $option;
           }
           $expArray[] = [
               'option' => $option,
               'isUpcomingAfterInitial' => $isUpcomingAfterInitial,
           ];
       }
       return response()->json(['date'=>$selectedDate]);
   }
   public function getoptiondata($type,$currentOptionSrike)
   {
       $expiryApiUrl = 'http://nimblerest.lisuns.com:4531/GetExpiryDates/?accessKey=988dcf72-de6b-4637-9af7-fddbe9bfa7cd&exchange=NFO&product=' . $type;
       $expApiResult = Http::get($expiryApiUrl)->json();
       $expDt = $expApiResult['EXPIRYDATES'] ?? [];
       $typeNft = $type;
       $currentTimestamp = time();
       $expArray = [];
       $selectedDate = null;
       foreach ($expDt as $option) {
           $carbonDate = Carbon::createFromFormat('dMY', $option);
           $timestamp = $carbonDate->timestamp;
           $isUpcomingOrCurrent = $timestamp >= $currentTimestamp;
           $isUpcomingAfterInitial = empty($selectedDate) && $isUpcomingOrCurrent;
           if ($isUpcomingAfterInitial) {
               $selectedDate = $option;
           }
           $expArray[] = [
               'option' => $option,
               'isUpcomingAfterInitial' => $isUpcomingAfterInitial,
           ];
       }
       $apiEndpoint = 'http://nimblerest.lisuns.com:4531/GetLastQuoteOptionChain/?accessKey=988dcf72-de6b-4637-9af7-fddbe9bfa7cd&exchange=NFO&product=' . $type . '&expiry=' . $selectedDate;
       try {
           $apiResult = Http::get($apiEndpoint)->json();
           $putArr = [];
           $callArr = [];
           $finalcallputvalue = [];
           foreach ($apiResult as $result) {
               $identi = explode('_', $result['INSTRUMENTIDENTIFIER']);
               $value = end($identi);      
               if ($result['SERVERTIME'] > 0) {
                   if ($identi[3] == 'CE') {
                       $callArr[] = array_merge($result, ['value' => (int)$value,'optionType' =>$identi[3],'optiondate' =>$identi[2]]);
                   } elseif ($identi[3] == 'PE') {
                       $putArr[] = array_merge($result, ['value' =>  (int)$value,'optionType' =>$identi[3],'optiondate' =>$identi[2]]);
                   }
               }
           }
           
           $closest = 0;
         foreach ($callArr as $item) {
             if ($closest === null || abs($currentOptionSrike - $closest) > abs($item['value'] - $currentOptionSrike)) {
                 $closest = $item['value'];
             }
         }
         $index = -1;
         // Iterate through the array and search for the value
         foreach ($callArr as $key => $subArray) {
             if (in_array($closest, $subArray)) {
                 $index = $key;
                 break;
             }
         }
         $otchangeCE=0;
         $otchangePE=0;
         if ($index !== -1) {
             $dataList = [];
             for ($i = $index - 6; $i < $index + 7; $i++) {
               $otchangeCE+=$callArr[$i]['OPENINTERESTCHANGE'];
               $otchangePE+=$putArr[$i]['OPENINTERESTCHANGE'];
              
             }
             $dataList[] = [
               'put' => $otchangePE,
               'call' =>$otchangeCE,
               'strike' => $closest,
           ];
         }
       return response()->json(['data'=>$dataList]);
       } catch (\Exception $e) {
           error_log($e->getMessage());
       }
       
   }
}
