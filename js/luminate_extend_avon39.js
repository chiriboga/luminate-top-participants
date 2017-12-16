//console.log('Luminate Version:',luminateExtend.library.version);
luminateExtend.init({
  apiKey: 'BF_Avon', 
  path: {
    nonsecure: 'http://www.avon39.org/site/', 
    secure: 'https://secure.avon39.org/site/'
  }
});
var myLoginCallback = function(data) {
  console.log('data.loginResponse',data.loginResponse);
	if(data.loginResponse === undefined){
		$('.user_login').show();
		$('#profileContainer').hide();
    Cookies.remove('a39_cons_id');
	} else {
		$('#profileContainer').append('<a href="https://secure.avon39.org/site/UserLogin?logout=0&NEXTURL=https://abccqa.com/testing/luminate.php" class="user_loggedin">Log out</a>  |  <a href="'+ luminateExtend.global.path.secure +'SPageServer?pagename=walk_login_landing" class="user_loggedin">Participant Center</a>');
		$('#profileContainer').show();
    $('.user_login').hide();
	}
};
var getUserCallback = function(data) {
    //console.log('getUserCallback:',data);
    $('#profileContainer').prepend('Welcome back, '+ data.getConsResponse.name.first + '  |  ');
    Cookies.set('a39_cons_id',data.getConsResponse.cons_id); // This ONLY works when plugin is SET!!!
    console.log(Cookies.get('a39_cons_id'));
}
var myErrorCallback = function() // error function. we could pass some message, but for now we just kill the cookie.
{
  Cookies.remove('a39_cons_id');
}
luminateExtend.api.request([{
  api: 'cons', 
  data: 'method=loginTest',
    callback: {
    success: myLoginCallback,
    error: myErrorCallback
  }
}, {
  async: false, 
  api: 'cons', 
  data: 'method=getUser', 
  requiresAuth: true, 
  callback: {
    success: getUserCallback,
    error: myErrorCallback
  }
}]);    
// https://secure2.convio.net/organization/site/CRContentAPI







