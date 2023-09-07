<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use View;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Carbon\Carbon;

class HomeController extends Controller
{

   public function index()
   {
      $latest_news = Blog::where('category', 'Latest News')->where('status', 1)->orderby('created_at', 'desc')->take(4)->get();
      return View::make('frontend.index', compact('latest_news'));
   }

   // News Details
   public function viewNews(Blog $blog)
   {
      return view('frontend.newsDetails', compact('blog'));
   }
   public function getChartData($type)
   {
      $currentNftData = 'http://nimblerest.lisuns.com:4531/GetLastQuote/?accessKey=988dcf72-de6b-4637-9af7-fddbe9bfa7cd&exchange=NFO&instrumentIdentifier=' . $type . '-I';
          $currentNftDataresult = Http::get($currentNftData)->json();
          $currentOptionSrike = $currentNftDataresult?$currentNftDataresult['AVERAGETRADEDPRICE']:null;
          
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
                          'put' => $putArr[$i]['OPENINTERESTCHANGE'],
                          'call' => $callArr[$i]['OPENINTERESTCHANGE'],
                          'putoi' => $putArr[$i]['OPENINTEREST'],
                          'calloi' => $callArr[$i]['OPENINTEREST'],
                          'label' => $callArr[$i]['value'],
                      ];
                  }
              }
          } catch (Exception $e) {
              error_log($e->getMessage());
          }
      return response()->json($dataList);
   }
   

}
