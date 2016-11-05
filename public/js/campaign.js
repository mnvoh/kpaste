/**
 * kPaste   -   kpaste.ir - kcoder.ir
 * 
 * @link        http://kcoder.ir/kpaste
 * @copyright   (c) 2013, kcoder.ir
 * @license     Proprietary
 * 
 * @filename    campaign.js
 * @createdat   Jul 22, 2013 4:41:57 PM
 */


function calculatePrice(totalViews, localFactor, adPrice, discountFactor)
{
    //      Discount Calculation Formula        //
    // NewPrice = ( totalViews / 1000 ) * adPrice * localFactor - (price * (totalViews / 1000 - 1) * discountFactor) - (price * (totalViews / 1000 - 1) * discountFactor) * Sine(( totalViews / 55000 ) * ( Math.PI / 3.7 ))
    var price = parseInt( ( totalViews / 1000 ) * adPrice * localFactor );
    var discountTrigonometricFactor = ( totalViews / 55000 ) * ( Math.PI / 3.7 );  //50000 is the max campaign credits
    var discount = price * (totalViews / 1000 - 1) * discountFactor;
    
    discount = Math.floor( discount - discount * Math.sin( discountTrigonometricFactor ) );

    var unitPrice = Math.floor((( price - discount ) / totalViews) * 10) / 10;
    
    return [price, discount, unitPrice];
}

function updatePrices()
{
    var totalViews = parseInt( $( '#campaignCredits' ).val() );
    var localFactor = ( $( '#campaignScope' ).val() === 'local' ) ? 
                        1 + parseFloat( $( '#localPriceFactor' ).val() ) : 1;
    var adPrice = parseInt( $( '#' + $( '#campaignType' ).val() + '_price' ).val() );
    var discountFactor = parseFloat( $( '#discountFactor' ).val() );
    prices = calculatePrice(totalViews, localFactor, adPrice, discountFactor);
    $( '#totalPrice' ).html( prices[0] );
    $( '#totalPriceDiscounted' ).html( prices[0] - prices[1] );
    $( '#unitPrice' ).html( prices[2] );
}

function updateExtraCreditsPrices()
{
    var totalViews = parseInt( $( '#extraCredits' ).val() );
    var localFactor = ( $( '#campaignScope' ).val() === 'local' ) ? 
                        1 + parseFloat( $( '#localPriceFactor' ).val() ) : 1;
    var adPrice = parseInt( $( '#campaignPrice' ).val() );
    var discountFactor = parseFloat( $( '#discountFactor' ).val() );
    prices = calculatePrice(totalViews, localFactor, adPrice, discountFactor);
    $( '#totalPrice' ).html( prices[0] );
    $( '#totalPriceDiscounted' ).html( prices[0] - prices[1] );
    $( '#unitPrice' ).html( prices[2] );
}

function updateCoupon(lang)
{
    var couponid = $('#couponid').val();
    $.getJSON('/'+lang+'/Ajax/getCoupon/' + couponid, function(data) {
        $('span#couponDiscount').html(data['result']);
    });
}

$('#campaignCredits, #campaignScope, #campaignType').change(function() {
    updatePrices();
});

$('#extraCredits').change(function() {
    updateExtraCreditsPrices();
});