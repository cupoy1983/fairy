<?php
require_once FANWE_ROOT."sdks/yiqifa/YiqifaUtils.php";
class YiqifaOpen
{
    var $consumerKey;
    var $consumerSecret;
    function __construct($key,$secret)
	{      
		$this->consumerKey = $key;
		$this->consumerSecret = $secret;
    }
    
    /**
     * 分页查询购物客合作商家
     * @return
     */
    function getMerchants($category="",$page=1,$rowCount=100){ 
        
        $url = YiqifaUtils::getBaseUrl()."/merchant.json?cat=".$category."&page=".$page."&pageRowCount=".$rowCount;
        $result = YiqifaUtils::sendRequest($url,$this->consumerKey,$this->consumerSecret);
        return $result;
    }
	
    /**
     *  查询购物客购物客商品分类
     * @return 一级分类
     */
	function getCategoryList()
	{ 
		$url = YiqifaUtils::getBaseUrl()."/category.json";
		$result = YiqifaUtils::sendRequest($url,$this->consumerKey,$this->consumerSecret);
		return $result;
	}
	
    /**
     * 分页查询购物客商品二级分类
     * @return
     */
    function getSubCategory($category,$page=1,$rowCount=100)
	{
        $url = YiqifaUtils::getBaseUrl()."/category/subcategory.json?category=".urlencode($category)."&page=".$page."&pageRowCount=".$rowCount;
        $result = YiqifaUtils::sendRequest($url,$this->consumerKey,$this->consumerSecret);
        return $result;
    }
	
    /**
     * 分页查询购物客商品
     * @return
     */
    function searchProduct($params,$page=1,$rowCount=18)
	{
        
        $url = YiqifaUtils::getBaseUrl()."/product/search.json";
        $url .= "?keyword=".$params['keyword'];
        if(isset($params['category'])){
            $url .= "&category=".$params['category'];    
        }
        if(isset($params['merchantids'])){
            $url .= "&merchantids=".$params['merchantids'];    
        }
        if(isset($params['minprice'])){
            $url .= "&minprice=".$params['minprice'];    
        }
        if(isset($params['maxprice'])){
            $url .= "&maxprice=".$params['maxprice'];    
        }
        if(isset($params['ordertype'])){
            $url .= "&ordertype=".$params['ordertype'];    
        }
        
        $url .="&page=".$page."&rowcount=".$rowCount;
        $result = YiqifaUtils::sendRequest($url,$this->consumerKey,$this->consumerSecret);
        return $result;
    }
	
	/**
     * 查询商品单品信息
     * @return
     */
    function singleProduct($params)
	{
        $url = YiqifaUtils::getBaseUrl()."/product/singleproduct.json";
        $url .= "?productUrl=".$params['productUrl'];
        if(isset($params['merchantId'])){
            $url .= "&merchantId=".$params['merchantId'];    
        }
        if(isset($params['productId'])){
            $url .= "&productId=".$params['productId'];    
        }
        if(isset($params['websiteId'])){
            $url .= "&websiteId=".$params['websiteId'];    
        }
        if(isset($params['feedback'])){
            $url .= "&feedback=".$params['feedback'];    
        }
		
        $result = YiqifaUtils::sendRequest($url,$this->consumerKey,$this->consumerSecret);
        return $result;
    }
}
?>
