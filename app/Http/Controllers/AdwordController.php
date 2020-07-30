<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSessionBuilder; 
use Google\AdsApi\AdWords\v201809\cm\AdGroupService;
use Google\AdsApi\AdWords\v201809\cm\AdGroupCriterionOperation;
use Google\AdsApi\AdWords\v201809\cm\AdGroupCriterionService;
use Google\AdsApi\AdWords\v201809\cm\BiddableAdGroupCriterion;
use Google\AdsApi\AdWords\v201809\cm\BiddingStrategyConfiguration;
use Google\AdsApi\AdWords\v201809\cm\CpcBid;
use Google\AdsApi\AdWords\v201809\cm\Keyword;
use Google\AdsApi\AdWords\v201809\cm\Criterion;
use Google\AdsApi\AdWords\v201809\cm\KeywordMatchType;
use Google\AdsApi\AdWords\v201809\cm\Money;
use Google\AdsApi\AdWords\v201809\cm\NegativeAdGroupCriterion;
use Google\AdsApi\AdWords\v201809\cm\Operator;
use Google\AdsApi\AdWords\v201809\cm\UrlList;
use Google\AdsApi\AdWords\v201809\cm\UserStatus;
use Google\AdsApi\AdWords\v201809\cm\Campaign;
use Google\AdsApi\AdWords\v201809\cm\CampaignOperation;
use Google\AdsApi\AdWords\v201809\cm\CampaignService;
use Google\AdsApi\AdWords\v201809\cm\CampaignStatus;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\CriterionType;

use Google\AdsApi\Common\OAuth2TokenBuilder;
use Session;
use Google\AdsApi\AdWords\Reporting\v201809\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDownloader;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinition;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinitionDateRangeType;
use Google\AdsApi\AdWords\v201809\cm\ReportDefinitionReportType;
 // ideas libs
 use Google\AdsApi\AdWords\v201809\o\AttributeType;
use Google\AdsApi\AdWords\v201809\o\IdeaType;
use Google\AdsApi\AdWords\v201809\o\LanguageSearchParameter;
use Google\AdsApi\AdWords\v201809\o\NetworkSearchParameter;
use Google\AdsApi\AdWords\v201809\o\RelatedToQuerySearchParameter;
use Google\AdsApi\AdWords\v201809\o\RequestType;
use Google\AdsApi\AdWords\v201809\o\SeedAdGroupIdSearchParameter;
use Google\AdsApi\AdWords\v201809\o\TargetingIdeaSelector;
use Google\AdsApi\AdWords\v201809\o\TargetingIdeaService;
use Google\AdsApi\Common\Util\MapEntries;

// negative 
use Google\AdsApi\AdWords\v201809\cm\ContentLabel;
use Google\AdsApi\AdWords\v201809\cm\ContentLabelType;
use Google\AdsApi\AdWords\v201809\cm\CustomerNegativeCriterion;
use Google\AdsApi\AdWords\v201809\cm\CustomerNegativeCriterionOperation;
use Google\AdsApi\AdWords\v201809\cm\CustomerNegativeCriterionService;
use Google\AdsApi\AdWords\v201809\cm\Placement;
use Google\AdsApi\AdWords\v201809\cm\CampaignSharedSet;
use Google\AdsApi\AdWords\v201809\cm\CampaignSharedSetOperation;
use Google\AdsApi\AdWords\v201809\cm\CampaignSharedSetService;
use Google\AdsApi\AdWords\v201809\cm\SharedCriterion;
use Google\AdsApi\AdWords\v201809\cm\SharedCriterionOperation;
use Google\AdsApi\AdWords\v201809\cm\SharedCriterionService;
use Google\AdsApi\AdWords\v201809\cm\SharedSet;
use Google\AdsApi\AdWords\v201809\cm\SharedSetOperation;
use Google\AdsApi\AdWords\v201809\cm\SharedSetService;
use Google\AdsApi\AdWords\v201809\cm\SharedSetType;
use Google\AdsApi\AdWords\v201809\cm\SharedSetStatus;

class AdwordController extends Controller
{
    private $session;
    private $test;
    const PAGE_LIMIT = 500;

    public function __construct()
    {
        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->fromFile()
            ->build();

        // Construct an API session configured from a properties file and the OAuth2
        // credentials above.
        // You can use withClientCustomerId() of AdWordsSessionBuilder to specify
        $this->session = (new AdWordsSessionBuilder())
            ->fromFile()
            ->withOAuth2Credential($oAuth2Credential)
            ->build();
    }

    public function pauseCampaign(Request $request)
    {   
        $p_id_campaign = $request['id_campaign'];
        
        $adWordsServices = new AdWordsServices();

        $campaignService = $adWordsServices->get($this->session, CampaignService::class);

        $operations = [];
        // Create a campaign with PAUSED status.
        $campaign = new Campaign();
        $campaign->setId($p_id_campaign);
        $campaign->setStatus(CampaignStatus::PAUSED);

        // Create a campaign operation and add it to the list.
        $operation = new CampaignOperation();
        $operation->setOperand($campaign);
        $operation->setOperator(Operator::SET);
        $operations[] = $operation;

        // Update the campaign on the server.
        $result = $campaignService->mutate($operations);

        $campaign = $result->getValue()[0];

        $msg = "Campaign with ID " . $campaign->getId() . ", name: " . $campaign->getName() . ", and budget delivery method " . $campaign->getBudget()->getDeliveryMethod() . " was"
            . " updated.\n";

        $return = (object) ['msg' => $msg];
        $response = [];
        array_push($response, $return);

        return $response;

    }

    public function playCampaign(Request $request)
    {

        $p_id_campaign = $request['id_campaign'];
        
        $adWordsServices = new AdWordsServices();

        $campaignService = $adWordsServices->get($this->session, CampaignService::class);

        $operations = [];
        // Create a campaign with ENABLED status.
        $campaign = new Campaign();
        $campaign->setId($p_id_campaign);
        $campaign->setStatus(CampaignStatus::ENABLED);

        // Create a campaign operation and add it to the list.
        $operation = new CampaignOperation();
        $operation->setOperand($campaign);
        $operation->setOperator(Operator::SET);
        $operations[] = $operation;

        // Update the campaign on the server.
        $result = $campaignService->mutate($operations);

        $campaign = $result->getValue()[0];

        $msg = "Campaign with ID " . $campaign->getId() . ", name: " . $campaign->getName() . ", and budget delivery method " . $campaign->getBudget()->getDeliveryMethod() . " was"
            . " updated.\n";

        $return = (object) ['msg' => $msg];
        $response = [];
        array_push($response, $return);

        return $response;

    }
    public function KeywordPlay(Request $request)
    {

        $ad_group_id = $request['ad_group_id'];
        $keyword_id = $request['keyword_id'];
        $status = $request['status'];
        
        $adWordsServices = new AdWordsServices();

        $criterionService = $adWordsServices->get($this->session, AdGroupCriterionService::class);

        $operations = [];
        // Create a campaign with ENABLED status.
        $criterion = new Criterion();
        $criterion->setId($keyword_id);

        $adGroupCriterion = new BiddableAdGroupCriterion();
        $adGroupCriterion->setAdGroupId($ad_group_id);
        $adGroupCriterion->setCriterion($criterion);

        $adGroupCriterion->setUserStatus(UserStatus::PAUSED);

        if ($status === "ENABLED") {
            $adGroupCriterion->setUserStatus(UserStatus::ENABLED);
        }
        
        // Create a campaign operation and add it to the list.
        $operation = new AdGroupCriterionOperation();
        $operation->setOperand($adGroupCriterion);
        $operation->setOperator(Operator::SET);
        $operations[] = $operation;

        // Update the campaign on the server.
        $result = $criterionService->mutate($operations);

        $campaign = $result->getValue()[0];

        $msg = " was"
            . " updated.\n";

        $return = (object) ['msg' => $msg];
        $response = [];
        array_push($response, $return);

        return $response;

    }
    public function getKeywordStatus(Request $request)
    {
        $ad_group_id = $request['ad_group_id'];
        $keyword_id = $request['keyword_id'];
        $adWordsServices = new AdWordsServices();
        $campaignService = $adWordsServices->get($this->session, AdGroupCriterionService::class);
        // Create AWQL query.
        $query = 'SELECT Id, AdGroupId, Status, KeywordText WHERE AdGroupId = ' . $ad_group_id . ' AND Id = ' . $keyword_id;
        // Create paging controls.
        $totalNumEntries = 0;
        $offset = 0;
        $array_res = [];
        do {
            $pageQuery = sprintf('%s LIMIT %d,%d', $query, $offset, self::PAGE_LIMIT);
            // Make the query request.
            $page = $campaignService->query($pageQuery);
            // Display results from the query.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $campaign) {
                    $AdWord = [
                        "id"=>$campaign->getCriterion()->getId(),
                        "adGroupId"=>$campaign->getAdGroupId(),
                        "keyword_text"=>$campaign->getCriterion()->getText(),
                        "status"=>$campaign->getUserStatus(),
                    ];
                    array_push(
                        $array_res,
                        $AdWord
                    );
                }
            }
            // Advance the paging offset.
            $offset += self::PAGE_LIMIT;
        } while ($offset < $totalNumEntries);
        return $array_res;
    }
    public function getEnabledCampaigns()
    {
        $adWordsServices = new AdWordsServices();

        $campaignService = $adWordsServices->get($this->session, CampaignService::class);

        // Create AWQL query.
        $query = 'SELECT Id, Name, Status  WHERE Status = ENABLED ';

        // Create paging controls.
        $totalNumEntries = 0;
        $offset = 0;

        $array_res = [];

        do {
            $pageQuery = sprintf('%s LIMIT %d,%d', $query, $offset, self::PAGE_LIMIT);
            // Make the query request.
            $page = $campaignService->query($pageQuery);

            // Display results from the query.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $campaign) {
                    $AdWord = ["id"=>$campaign->getId(),"name"=>$campaign->getName()];
                    array_push(
                        $array_res,
                        $AdWord
                    );
                }
            }

            // Advance the paging offset.
            $offset += self::PAGE_LIMIT;
        } while ($offset < $totalNumEntries);

        return $array_res;

    }
    public function getAllCampaigns()
    {
        $adWordsServices = new AdWordsServices();
        $campaignService = $adWordsServices->get($this->session, CampaignService::class);
        // Create AWQL query.
        $query = 'SELECT Id, Name, Status';
        // Create paging controls.
        $totalNumEntries = 0;
        $offset = 0;
        $array_res = [];
        do {
            $pageQuery = sprintf('%s LIMIT %d,%d', $query, $offset, self::PAGE_LIMIT);
            // Make the query request.
            $page = $campaignService->query($pageQuery);

            // Display results from the query.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $campaign) {
                    $AdWord = ["id"=>$campaign->getId(),"name"=>$campaign->getName()];
                    array_push(
                        $array_res,
                        $AdWord
                    );
                }
            }

            // Advance the paging offset.
            $offset += self::PAGE_LIMIT;
        } while ($offset < $totalNumEntries);

        return $array_res;

    }

    public  function getAdGroups(Request $request) {
        
        $campaignId = $request['campaignId'];
        $adWordsServices = new AdWordsServices();

        $adGroupService = $adWordsServices->get($this->session, AdGroupService::class);

        // Create a selector to select all ad groups for the specified campaign.
        $selector = new Selector();
        $selector->setFields(['Id', 'Name']);
        $selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
        $selector->setPredicates(
            [
                new Predicate('CampaignId', PredicateOperator::EQUALS, [$campaignId]),
            ]
        );
        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));

        $totalNumEntries = 0;
        $array_res=[];
        do {
            // Retrieve ad groups one page at a time, continuing to request pages
            // until all ad groups have been retrieved.
            $page = $adGroupService->get($selector);

            // Print out some information for each ad group.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $adGroup) {
                    $entries = ["id"=>$adGroup->getId(),"name"=>$adGroup->getName()];
                    array_push(
                        $array_res,
                        $entries
                    );
                }
            }

            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

        return $array_res;

    }


    // get all KeywordsReport
    public function getKeyWords( Request $request)
    {
        $CampaignId = $request['CampaignId'];
        $dateRange = $request['dateRange'];
         // Create selector.
         $selector = new Selector();
         $selector->setFields(
             [      
                 'Criteria',
                 'Labels',
                 'Clicks',
                 'Cost',
                 'Impressions',
                 'QualityScore',
                 'PostClickQualityScore',
                 'AverageCost',
                 'Id',
                 'AdGroupName',
                 'AdGroupId',
                 'TopOfPageCpc',
                 'FirstPositionCpc',
                 'FirstPageCpc',
                 'ClickType',
                 'Interactions',
                 'Conversions',
                 'CampaignId',
                 'CampaignName',
                 'CpcBid',
             ]
         );
 
         /* // Use a predicate to filter out paused criteria (this is optional).
         $selector->setPredicates(
             [
                 new Predicate('HasQualityScore', PredicateOperator::EQUALS, ['TRUE']),
                 new Predicate('CampaignStatus', PredicateOperator::IN, ['ENABLED']),
                 new Predicate('Status', PredicateOperator::IN, ['ENABLED']),
                 new Predicate('IsNegative', PredicateOperator::IN, ['FALSE']),
                 new Predicate('ClickType', PredicateOperator::EQUALS, ['CALLS']),
                                  
             ]
         ); */
          // Create report definition.
        $reportDefinition = new ReportDefinition();
        $reportDefinition->setSelector($selector);
        $reportDefinition->setReportName(
            'AdGroup performance report #' . uniqid()
        );
        switch ($dateRange) {
            case 'TODAY':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::TODAY
            );
                break;
            case 'YESTERDAY':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::YESTERDAY
            );
            break;
            case 'THIS_WEEK_SUN_TODAY':
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::THIS_WEEK_SUN_TODAY
                );
                break;
            case 'THIS_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::THIS_MONTH
            );
            break;
            case 'LAST_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::LAST_MONTH
            );
            break;
            default:
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::YESTERDAY
                );
            break;
        }
        
        $reportDefinition->setReportType(
            ReportDefinitionReportType::CRITERIA_PERFORMANCE_REPORT
        );
        $reportDefinition->setDownloadFormat(DownloadFormat::XML);

        $reportDownloader = new ReportDownloader($this->session);
        // Optional: If you need to adjust report settings just for this one
        $reportSettingsOverride = (new ReportSettingsBuilder())->includeZeroImpressions(false)->build();
        $reportDownloadResult = $reportDownloader->downloadReport(
           $reportDefinition
        );
        // print "Report was downloaded and printed below:\n";
        
        $xml = simplexml_load_string($reportDownloadResult->getAsString()); //where $xml_string is the fetched xml content
        $array_res=[];
        foreach($xml->table->row as $item) {
            $entries = [
                
                "keyword"=>$item->attributes()->keywordPlacement,
                "labels"=>$item->attributes()->labels,
                "clicks"=>$item->attributes()->clicks,
                "cost"=>$item->attributes()->cost,
                "impressions"=>$item->attributes()->impressions,
                "qualityScore"=>$item->attributes()->qualityScore,
                "landingPageExperience"=>$item->attributes()->landingPageExperience,
                "avgCost"=>$item->attributes()->avgCost,
                "keywordID"=>$item->attributes()->keywordID,
                "AdGroupName"=>$item->attributes()->adGroup,
                "adGroupID"=>$item->attributes()->adGroupID,
                "topOfPageCPC"=>$item->attributes()->topOfPageCPC,              
                "firstPositionCPC"=>$item->attributes()->firstPositionCPC,
                "firstPageCPC"=>$item->attributes()->firstPageCPC,
                "clickType"=>$item->attributes()->clickType,
                "interactions"=>$item->attributes()->interactions,
                "conversions"=>$item->attributes()->conversions,
                "campaignID"=>$item->attributes()->campaignID,
                "campaign"=>$item->attributes()->campaign,
                "maxCPC"=>$item->attributes()->maxCPC,
            ];
            array_push(
                $array_res,
                $entries
            );
        }
        //$json = json_encode($xml); //Json Object
        //$array_return = json_decode($json,TRUE); //If you need to consume it as an array
        
        // return $reportDownloadResult->getAsString();
        return $array_res;
    }  

    public function getSingleKeyWords( Request $request)
    {
        $CampaignId = $request['CampaignId'];
        $dateRange = $request['dateRange'];
         // Create selector.
         $selector = new Selector();
         $selector->setFields(
             [      
                 'Criteria',
                 'Labels',
                 'Clicks',
                 'Cost',
                 'Impressions',
                 'QualityScore',
                 'PostClickQualityScore',
                 'AverageCost',
                 'Id',
                 'AdGroupName',
                 'AdGroupId',
                 'TopOfPageCpc',
                 'FirstPositionCpc',
                 'FirstPageCpc',
                 'ClickType',
                 'Interactions',
                 'Conversions',
                 'CpcBid', 
                 'CampaignId',
                 'CampaignName',
             ]
         );
 
         // Use a predicate to filter out paused criteria (this is optional).
         $selector->setPredicates(
             [
                 new Predicate('CampaignId', PredicateOperator::EQUALS, [$CampaignId]),
             ]
         );
          // Create report definition.
        $reportDefinition = new ReportDefinition();
        $reportDefinition->setSelector($selector);
        $reportDefinition->setReportName(
            'AdGroup performance report #' . uniqid()
        );
        switch ($dateRange) {
            case 'TODAY':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::TODAY
            );
                break;
            case 'YESTERDAY':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::YESTERDAY
            );
            break;
            case 'THIS_WEEK_SUN_TODAY':
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::THIS_WEEK_SUN_TODAY
                );
                break;
            case 'THIS_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::THIS_MONTH
            );
            break;
            case 'LAST_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::LAST_MONTH
            );
            break;
            default:
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::YESTERDAY
                );
            break;
        }
        
        $reportDefinition->setReportType(
            ReportDefinitionReportType::CRITERIA_PERFORMANCE_REPORT
        );
        $reportDefinition->setDownloadFormat(DownloadFormat::XML);

        $reportDownloader = new ReportDownloader($this->session);
        // Optional: If you need to adjust report settings just for this one
        $reportSettingsOverride = (new ReportSettingsBuilder())->includeZeroImpressions(false)->build();
        $reportDownloadResult = $reportDownloader->downloadReport(
           $reportDefinition
        );
        // print "Report was downloaded and printed below:\n";
        
        $xml = simplexml_load_string($reportDownloadResult->getAsString()); //where $xml_string is the fetched xml content
        $array_res=[];
        foreach($xml->table->row as $item) {
            $entries = [
                
                "keyword"=>$item->attributes()->keywordPlacement,
                "labels"=>$item->attributes()->labels,
                "clicks"=>$item->attributes()->clicks,
                "cost"=>$item->attributes()->cost,
                "impressions"=>$item->attributes()->impressions,
                "qualityScore"=>$item->attributes()->qualityScore,
                "landingPageExperience"=>$item->attributes()->landingPageExperience,
                "avgCost"=>$item->attributes()->avgCost,
                "keywordID"=>$item->attributes()->keywordID,
                "AdGroupName"=>$item->attributes()->adGroup,
                "adGroupID"=>$item->attributes()->adGroupID,
                "topOfPageCPC"=>$item->attributes()->topOfPageCPC,              
                "firstPositionCPC"=>$item->attributes()->firstPositionCPC,
                "firstPageCPC"=>$item->attributes()->firstPageCPC,
                "clickType"=>$item->attributes()->clickType,
                "interactions"=>$item->attributes()->interactions,
                "conversions"=>$item->attributes()->conversions,
                "maxCPC"=>$item->attributes()->maxCPC,
                "campaignID"=>$item->attributes()->campaignID,
                "campaign"=>$item->attributes()->campaign,
            ];
            array_push(
                $array_res,
                $entries
            );
        }
        //$json = json_encode($xml); //Json Object
        //$array_return = json_decode($json,TRUE); //If you need to consume it as an array
        
        // return $reportDownloadResult->getAsString();
        return $array_res;
    }  

    public function getSingleAdGroupReport(Request $request)
    {
        $campaignId = $request['campaignId'];
        $dateRange = $request['dateRange'];
        //$dateRange =constant("Google\AdsApi\AdWords\v201809\cm\ReportDefinitionDateRangeType::$dateRange");
        
         // Create selector.
         $selector = new Selector();
         $selector->setFields(
             [
                 'AdGroupName',
                 'Conversions',
                 'Cost',
                 'CpcBid',
                 'Clicks',
                 'CampaignId',
                 'CampaignName',
                 'AdGroupId',
                 'Impressions',
             ]
         );
 
         // Use a predicate to filter out paused criteria (this is optional).
         $selector->setPredicates(
             [
                 new Predicate('CampaignId', PredicateOperator::EQUALS, [$campaignId]),
             ]
         );
          // Create report definition.
        $reportDefinition = new ReportDefinition();
        $reportDefinition->setSelector($selector);
        $reportDefinition->setReportName(
            'AdGroup performance report #' . uniqid()
        );
        switch ($dateRange) {
            case 'TODAY':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::TODAY
            );
                break;
            case 'THIS_WEEK_SUN_TODAY':
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::THIS_WEEK_SUN_TODAY
                );
                break;
            case 'THIS_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::THIS_MONTH
            );
            break;
            case 'LAST_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::LAST_MONTH
            );
            break;
            default:
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::TODAY
                );
            break;
        }
        
        $reportDefinition->setReportType(
            ReportDefinitionReportType::ADGROUP_PERFORMANCE_REPORT
        );
        $reportDefinition->setDownloadFormat(DownloadFormat::XML);

        $reportDownloader = new ReportDownloader($this->session);
        // Optional: If you need to adjust report settings just for this one
        $reportSettingsOverride = (new ReportSettingsBuilder())->includeZeroImpressions(false)->build();
        $reportDownloadResult = $reportDownloader->downloadReport(
           $reportDefinition
        );
        // print "Report was downloaded and printed below:\n";
        
        $xml = simplexml_load_string($reportDownloadResult->getAsString()); //where $xml_string is the fetched xml content
        $array_res=[];
        
        foreach($xml->table->row as $item) {
            $entries = [
                "adGroup"=>$item->attributes()->adGroup,
                "clicks"=>$item->attributes()->clicks,
                "cost"=>$item->attributes()->cost,
                "impressions"=>$item->attributes()->impressions,
                "conversions"=>$item->attributes()->conversions,            
                "defaultMaxCPC"=>$item->attributes()->defaultMaxCPC,            
                "campaignID"=>$item->attributes()->campaignID,            
                "campaign"=>$item->attributes()->campaign,            
                "adGroupID"=>$item->attributes()->adGroupID,            
            ];
            array_push(
                $array_res,
                $entries
            );
        }
        //$json = json_encode($xml); //Json Object
        //$array_return = json_decode($json,TRUE); //If you need to consume it as an array
        
        // return $reportDownloadResult->getAsString();
        return $array_res;
    }

    public function getAllAdGroupReport(Request $request)
    {
        $campaignId = $request['campaignId'];
        $dateRange = $request['dateRange'];
        //$dateRange =constant("Google\AdsApi\AdWords\v201809\cm\ReportDefinitionDateRangeType::$dateRange");
        
         // Create selector.
         $selector = new Selector();
         $selector->setFields(
             [
                 'AdGroupName',
                 'Conversions',
                 'Cost',
                 'CpcBid',
                 'Clicks',
                 'CampaignId',
                 'CampaignName',
                 'AdGroupId',
                 'Impressions',
             ]
         );
 
        /*  // Use a predicate to filter out paused criteria (this is optional).
         $selector->setPredicates(
             [
                new Predicate('CampaignStatus', PredicateOperator::EQUALS, ['ENABLED']),
                 new Predicate('AdGroupStatus', PredicateOperator::EQUALS, ['ENABLED']),
             ]
         ); */
          // Create report definition.
        $reportDefinition = new ReportDefinition();
        $reportDefinition->setSelector($selector);
        $reportDefinition->setReportName(
            'AdGroup performance report #' . uniqid()
        );
        switch ($dateRange) {
            case 'TODAY':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::TODAY
            );
                break;
            case 'THIS_WEEK_SUN_TODAY':
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::THIS_WEEK_SUN_TODAY
                );
                break;
            case 'THIS_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::THIS_MONTH
            );
            break;
            case 'LAST_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::LAST_MONTH
            );
            break;
            default:
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::YESTERDAY
                );
            break;
        }
        
        $reportDefinition->setReportType(
            ReportDefinitionReportType::ADGROUP_PERFORMANCE_REPORT
        );
        $reportDefinition->setDownloadFormat(DownloadFormat::XML);

        $reportDownloader = new ReportDownloader($this->session);
        // Optional: If you need to adjust report settings just for this one
        $reportSettingsOverride = (new ReportSettingsBuilder())->includeZeroImpressions(false)->build();
        $reportDownloadResult = $reportDownloader->downloadReport(
           $reportDefinition
        );
        // print "Report was downloaded and printed below:\n";
        
        $xml = simplexml_load_string($reportDownloadResult->getAsString()); //where $xml_string is the fetched xml content
        $array_res=[];
        
        foreach($xml->table->row as $item) {
            $entries = [
                "adGroup"=>$item->attributes()->adGroup,
                "clicks"=>$item->attributes()->clicks,
                "cost"=>$item->attributes()->cost,
                "impressions"=>$item->attributes()->impressions,
                "conversions"=>$item->attributes()->conversions,            
                "defaultMaxCPC"=>$item->attributes()->defaultMaxCPC,            
                "campaignID"=>$item->attributes()->campaignID,            
                "campaign"=>$item->attributes()->campaign,            
                "adGroupID"=>$item->attributes()->adGroupID,           
            ];
            array_push(
                $array_res,
                $entries
            );
        }
        //$json = json_encode($xml); //Json Object
        //$array_return = json_decode($json,TRUE); //If you need to consume it as an array
        
        // return $reportDownloadResult->getAsString();
        return $array_res;
    }
    public function getAdPerformanceReport(Request $request)
    {
        $campaignId = $request['campaignId'];
        $dateRange = $request['dateRange'];
        
         $selector = new Selector();
         $selector->setFields(
             [
                 'CriterionId',
                 'AdGroupId',
                 'AdGroupName',
                 'Clicks',
                 'Cost',
                 'CampaignId',
                 'CampaignName',
                 'Device',
                 'Description',
                 'Description1',
                 'Description2',
                 'Id',
                 'DisplayUrl',
                 'Headline',
             ]
         );
 
         // Use a predicate to filter out paused criteria (this is optional).
         $selector->setPredicates(
             [
                 new Predicate('CriterionType', PredicateOperator::EQUALS, ['KEYWORD']),
             ]
         );
          // Create report definition.
        $reportDefinition = new ReportDefinition();
        $reportDefinition->setSelector($selector);
        $reportDefinition->setReportName(
            'AdGroup performance report #' . uniqid()
        );
        switch ($dateRange) {
            case 'TODAY':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::TODAY
            );
                break;
            case 'THIS_WEEK_SUN_TODAY':
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::THIS_WEEK_SUN_TODAY
                );
                break;
            case 'THIS_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::THIS_MONTH
            );
            break;
            case 'LAST_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::LAST_MONTH
            );
            break;
            default:
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::YESTERDAY
                );
            break;
        }
        
        $reportDefinition->setReportType(
            ReportDefinitionReportType::AD_PERFORMANCE_REPORT
        );
        $reportDefinition->setDownloadFormat(DownloadFormat::XML);

        $reportDownloader = new ReportDownloader($this->session);
        // Optional: If you need to adjust report settings just for this one
        $reportSettingsOverride = (new ReportSettingsBuilder())->includeZeroImpressions(false)->build();
        $reportDownloadResult = $reportDownloader->downloadReport(
           $reportDefinition
        );
        // print "Report was downloaded and printed below:\n";
        
        $xml = simplexml_load_string($reportDownloadResult->getAsString()); //where $xml_string is the fetched xml content
        $array_res=[];
        
        foreach($xml->table->row as $item) {
            $entries = [
                "keywordID"=>$item->attributes()->keywordID,
                "adGroup"=>$item->attributes()->adGroup,
                "clicks"=>$item->attributes()->clicks,
                "cost"=>$item->attributes()->cost,
                "campaignID"=>$item->attributes()->campaignID,            
                "campaign"=>$item->attributes()->campaign,            
                "adGroupID"=>$item->attributes()->adGroupID,            
                "device"=>$item->attributes()->device,
                "description"=>$item->attributes()->description,
                "descriptionLine1"=>$item->attributes()->descriptionLine1,
                "descriptionLine2"=>$item->attributes()->descriptionLine2,
                "adID"=>$item->attributes()->adID,
                "ad"=>$item->attributes()->ad,
                "displayURL"=>$item->attributes()->displayURL,
            ];
            array_push(
                $array_res,
                $entries
            );
        }
        //$json = json_encode($xml); //Json Object
        //$array_return = json_decode($json,TRUE); //If you need to consume it as an array
        
        // return $reportDownloadResult->getAsString();
        return $array_res;
    }

    public function CallDeatailReport(Request $request)
    {
        $CampaignId = $request['CampaignId'];
        $dateRange = $request['dateRange'];
         // Create selector.
        // $adPerformanceReport = $this->getAdPerformanceReport($request);
        // dd($adPerformanceReport[0]);

         $selector = new Selector();
         $selector->setFields(
             [      
                 'AdGroupId',
                 'AdGroupName',
                 'CallDuration',
                 'CallEndTime',
                 'CallStartTime',
                 'CallStatus',
                 'CallType',
                 'CampaignId',
                 'CampaignName',
                 'CustomerDescriptiveName',
                 'Date',
                 'DayOfWeek',
                 'ExternalCustomerId',
                 'HourOfDay',
             ]
         );
 
         // Use a predicate to filter out paused criteria (this is optional).
         $selector->setPredicates(
             [
                 new Predicate('CampaignStatus', PredicateOperator::IN, ['ENABLED']),
             ]
         );
          // Create report definition.
        $reportDefinition = new ReportDefinition();
        $reportDefinition->setSelector($selector);
        $reportDefinition->setReportName(
            'AdGroup performance report #' . uniqid()
        );
        switch ($dateRange) {
            case 'TODAY':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::TODAY
            );
                break;
            case 'YESTERDAY':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::YESTERDAY
            );
            break;
            case 'THIS_WEEK_SUN_TODAY':
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::THIS_WEEK_SUN_TODAY
                );
                break;
            case 'THIS_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::THIS_MONTH
            );
            break;
            case 'LAST_MONTH':
            $reportDefinition->setDateRangeType(
                ReportDefinitionDateRangeType::LAST_MONTH
            );
            break;
            default:
                $reportDefinition->setDateRangeType(
                    ReportDefinitionDateRangeType::YESTERDAY
                );
            break;
        }
        
        $reportDefinition->setReportType(
            ReportDefinitionReportType::CALL_METRICS_CALL_DETAILS_REPORT
        );
        $reportDefinition->setDownloadFormat(DownloadFormat::XML);

        $reportDownloader = new ReportDownloader($this->session);
        // Optional: If you need to adjust report settings just for this one
        $reportSettingsOverride = (new ReportSettingsBuilder())->includeZeroImpressions(false)->build();
        $reportDownloadResult = $reportDownloader->downloadReport(
           $reportDefinition
        );
        // print "Report was downloaded and printed below:\n";
        
        $xml = simplexml_load_string($reportDownloadResult->getAsString()); //where $xml_string is the fetched xml content
        $array_res=[];
        foreach($xml->table->row as $item) {
            $entries = [
                
                "adGroupID"=>$item->attributes()->adGroupID,
                "adGroup"=>$item->attributes()->adGroup,
                "durationSeconds"=>$item->attributes()->durationSeconds,
                "endTime"=>$item->attributes()->endTime,
                "startTime"=>$item->attributes()->startTime,
                "status"=>$item->attributes()->status,
                "callType"=>$item->attributes()->callType,
                "campaignID"=>$item->attributes()->campaignID,
                "campaign"=>$item->attributes()->campaign,
                "clientName"=>$item->attributes()->clientName,
                "day"=>$item->attributes()->day,
                "dayOfWeek"=>$item->attributes()->dayOfWeek,
                "hourOfDay"=>$item->attributes()->hourOfDay,             
            ];
            array_push(
                $array_res,
                $entries
            );
        }
        return $array_res;
    }
    public function getKeywordIdeas(Request $request) {
        $keywords = explode( ',' , $request['keywords']);
        // $dateRange = $request['dateRange'];

        $adWordsServices = new AdWordsServices();
        $targetingIdeaService = $adWordsServices->get($this->session, TargetingIdeaService::class);
                // Create selector.
        $selector = new TargetingIdeaSelector();
        $selector->setRequestType(RequestType::IDEAS);
        $selector->setIdeaType(IdeaType::KEYWORD);
        $selector->setRequestedAttributeTypes(
            [
                AttributeType::KEYWORD_TEXT,
                AttributeType::SEARCH_VOLUME,
                AttributeType::AVERAGE_CPC,
                AttributeType::COMPETITION,
                AttributeType::CATEGORY_PRODUCTS_AND_SERVICES
            ]
        );
        $paging = new Paging();
        $paging->setStartIndex(0);
        $paging->setNumberResults(25);
        $selector->setPaging($paging);

        $searchParameters = [];
        // Create related to query search parameter.
        $relatedToQuerySearchParameter = new RelatedToQuerySearchParameter();
        $relatedToQuerySearchParameter->setQueries($keywords);
        $searchParameters[] = $relatedToQuerySearchParameter;
        // Get keyword ideas.
        $selector->setSearchParameters($searchParameters);
        $page = $targetingIdeaService->get($selector);
        $entries = $page->getEntries();
if ($entries !== null) {
    $result = [];
    foreach ($entries as $targetingIdea) {
        $data = MapEntries::toAssociativeArray($targetingIdea->getData());
        $keyword = $data[AttributeType::KEYWORD_TEXT]->getValue();
        $searchVolume = ($data[AttributeType::SEARCH_VOLUME]->getValue() !== null)
            ? $data[AttributeType::SEARCH_VOLUME]->getValue() : 0;
        $averageCpc = $data[AttributeType::AVERAGE_CPC]->getValue();
        $competition = $data[AttributeType::COMPETITION]->getValue();
        $categoryIds = ($data[AttributeType::CATEGORY_PRODUCTS_AND_SERVICES]->getValue() === null)
            ? $categoryIds = '' : implode(', ',$data[AttributeType::CATEGORY_PRODUCTS_AND_SERVICES]->getValue());
            array_push($result, (object) [ 'keyword' => $keyword,
            'searchVolume' => $searchVolume,
            'averageCpc' => ($averageCpc === null) ? 0 : $averageCpc->getMicroAmount(),
            'competition' => $competition,
            'categoryIds' => $categoryIds]);
    }
    return $result;
}
    }

// Add a negative campaign criterion.
public function AddNegativeKeyword (Request $request ) {
    $badKeyword = $request['keyword'];
    $isArray= false;
    if( strpos($badKeyword, ',') !== false ) {
        $badKeyword = explode(",",$request['keyword']);
        $isArray= true;
    }
    $matchType = $request['matchType'];
    $adWordsServices = new AdWordsServices();
    $sharedSetService = $adWordsServices->get(
        $this->session,
        SharedSetService::class
    );
    if ($isArray === false) {
        $keywords = [];
        $keywords[]= $badKeyword;
    } else {
        $keywords = $badKeyword;
    }

    // Create the shared negative keyword set.
    /* $sharedSet = new SharedSet();
    $sharedSet->setName('Negative keyword list #' . uniqid());
    $sharedSet->setType(SharedSetType::NEGATIVE_KEYWORDS);

    $sharedSetOperation = new SharedSetOperation();
    $sharedSetOperation->setOperator(Operator::ADD);
    $sharedSetOperation->setOperand($sharedSet);

    // Create the shared set on the server and print out some information.
    $sharedSet = $sharedSetService->mutate([$sharedSetOperation])->getValue()[0];
    printf(
        "Shared set with ID %d and name '%s' was successfully added.\n",
        $sharedSet->getSharedSetId(),
        $sharedSet->getName()
    ); */

   $sharedCriterionService = $adWordsServices->get(
        $this->session,
        SharedCriterionService::class
    );

    // Add negative keywords to the shared set. 1816048424
    $operations = [];
    foreach ($keywords as $keyword) {
        $keywordCriterion = new Keyword();
        $keywordCriterion->setText($keyword);
        switch ($matchType) {
            case 'EXACT':
                $keywordCriterion->setMatchType(KeywordMatchType::EXACT);
                break;
            case 'PHRASE':
                $keywordCriterion->setMatchType(KeywordMatchType::PHRASE);
                    break;
            case 'BROAD':
                $keywordCriterion->setMatchType(KeywordMatchType::BROAD);
                break;
            default:
            $keywordCriterion->setMatchType(KeywordMatchType::EXACT);
            break;
        }
        $sharedCriterion = new SharedCriterion();
        $sharedCriterion->setCriterion($keywordCriterion);
        $sharedCriterion->setNegative(true);
        $sharedCriterion->setSharedSetId(1816048424);

        $sharedCriterionOperation = new SharedCriterionOperation();
        $sharedCriterionOperation->setOperator(Operator::ADD);
        $sharedCriterionOperation->setOperand($sharedCriterion);

        $operations[] = $sharedCriterionOperation;
    }

    $result = $sharedCriterionService->mutate($operations);
    $res = [];
    foreach ($result->getValue() as $sharedCriterion) {
        array_push($res, (object) [
            'keyword' => $sharedCriterion->getCriterion()->getText(),
            'criterionId' => $sharedCriterion->getCriterion()->getId(),
            'sharedSetId' => $sharedCriterion->getSharedSetId(),
            'matchType'=>$matchType]);
    }
    return $res;
    /* $campaignSharedSetService = $adWordsServices->get(
        $this->session,
        CampaignSharedSetService::class
    );
    $campaignSharedSet = new CampaignSharedSet();
    $campaignSharedSet->setCampaignId(1549402064); // todos califican
    $campaignSharedSet->setSharedSetId(1816048424);

    $campaignSharedOperation = new CampaignSharedSetOperation();
    $campaignSharedOperation->setOperator(Operator::ADD);
    $campaignSharedOperation->setOperand($campaignSharedSet);

    $campaignSharedSet = $campaignSharedSetService->mutate([$campaignSharedOperation])->getValue()[0];
    printf(
        "Shared set ID %d was attached to campaign ID %d.\n",
        $campaignSharedSet->getSharedSetId(),
        $campaignSharedSet->getCampaignId()
    ); */
}
public function AddSharedSet (Request $request ) {
    $name = $request['name'];
    $adWordsServices = new AdWordsServices();
    $sharedSetService = $adWordsServices->get(
        $this->session,
        SharedSetService::class
    );

    // Create the shared negative keyword set.
    $sharedSet = new SharedSet();
    $sharedSet->setName($name);
    $sharedSet->setType(SharedSetType::NEGATIVE_KEYWORDS);

    $sharedSetOperation = new SharedSetOperation();
    $sharedSetOperation->setOperator(Operator::ADD);
    $sharedSetOperation->setOperand($sharedSet);

    // Create the shared set on the server and print out some information.
    $sharedSet = $sharedSetService->mutate([$sharedSetOperation])->getValue()[0];
    $res = [];
        array_push($res, (object) [
            'keyword' =>  $sharedSet->getName(),
            'sharedSetId' =>  $sharedSet->getSharedSetId()]);
    return $res;
}
public function toggleSharedSet (Request $request) {
    $status = $request['status'];
    $sharedSetId = $request['sharedSetId'];
    // initialize service
    $adWordsServices = new AdWordsServices();
    $sharedSetService = $adWordsServices->get(
        $this->session,
        SharedSetService::class
    );
     // Create the shared negative keyword set.
     $sharedSet = new SharedSet();
     $sharedSet->setSharedSetId($sharedSetId);
     $sharedSetOperation = new SharedSetOperation();
     switch ($status) {
         case 'REMOVED':
            $sharedSet->setStatus(SharedSetStatus::REMOVED);
            $sharedSetOperation->setOperator(Operator::REMOVE);
             break;
         case 'ENABLED':
            $sharedSet->setStatus(SharedSetStatus::ENABLED);
            $sharedSetOperation->setOperator(Operator::SET);
            break;
         default:
             # code...
             break;
     }
 
     $sharedSetOperation->setOperand($sharedSet);
      // Create the shared set on the server and print out some information.
      $sharedSet = $sharedSetService->mutate([$sharedSetOperation])->getValue()[0];
      $res = [];
          array_push($res, (object) [
              'sharedSetName' =>  $sharedSet->getName(),
              'sharedSetId' =>  $sharedSet->getSharedSetId(),
              'status' => $sharedSet->getStatus()
              ]);
      return $res;
    
}
public function AddNegativeKeywordCustomSharedSet (Request $request ) {
    $badKeyword = $request['keyword'];
    $matchType = $request['matchType'];
    $sharedSetId = $request['sharedSetId'];
    $adWordsServices = new AdWordsServices();
    $sharedSetService = $adWordsServices->get(
        $this->session,
        SharedSetService::class
    );

    $keywords = [];
    $keywords[]= $badKeyword;

   $sharedCriterionService = $adWordsServices->get(
        $this->session,
        SharedCriterionService::class
    );

    // Add negative keywords to the shared set.
    $operations = [];
    foreach ($keywords as $keyword) {
        $keywordCriterion = new Keyword();
        $keywordCriterion->setText($keyword);
        switch ($matchType) {
            case 'EXACT':
                $keywordCriterion->setMatchType(KeywordMatchType::EXACT);
                break;
            case 'PHRASE':
                $keywordCriterion->setMatchType(KeywordMatchType::PHRASE);
                    break;
            case 'BROAD':
                $keywordCriterion->setMatchType(KeywordMatchType::BROAD);
                break;
            default:
            $keywordCriterion->setMatchType(KeywordMatchType::EXACT);
            break;
        }
        $sharedCriterion = new SharedCriterion();
        $sharedCriterion->setCriterion($keywordCriterion);
        $sharedCriterion->setNegative(true);
        $sharedCriterion->setSharedSetId(sharedSetId);

        $sharedCriterionOperation = new SharedCriterionOperation();
        $sharedCriterionOperation->setOperator(Operator::ADD);
        $sharedCriterionOperation->setOperand($sharedCriterion);

        $operations[] = $sharedCriterionOperation;
    }

    $result = $sharedCriterionService->mutate($operations);
    $res = [];
    foreach ($result->getValue() as $sharedCriterion) {
        array_push($res, (object) [
            'keyword' => $sharedCriterion->getCriterion()->getText(),
            'criterionId' => $sharedCriterion->getCriterion()->getId(),
            'sharedSetId' => $sharedCriterion->getSharedSetId(),
            'matchType'=>$matchType]);
    }
    return $res;
}
}