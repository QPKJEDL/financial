layui.use(['laydate', 'laypage', 'layer', 'table', 'carousel', 'upload', 'element', 'slider','jquery'], function(){
	var laydate = layui.laydate //日期
		,laypage = layui.laypage //分页
		,layer = layui.layer //弹层
		,table = layui.table //表格
		,carousel = layui.carousel //轮播
		,upload = layui.upload //上传
		,element = layui.element //元素操作
		,slider = layui.slider //滑块
		,$ = layui.jquery
		,lock = false;

	//导航栏监听
	element.on('nav(leftNav)',function(obj){
		var _this = $(obj);
		var id = _this.attr('data-id');
		var str ='.menuList-'+id;
		//先把所有a标签的样式全部添加上
		$('.menuCli').each(function () {
			var _this = $(this);
			_this.css('display','none');
		});
		$(str).each(function () {
			var _this = $(this);
			_this.css('display','');
		});
		var list = $('.menuList').children('a[class="menuCli menuList-'+id+'"]');
		var chl = list[0];
		var chlID = $(chl).attr('data-id');
		var isActive = $(".layui-tab-title").find('li[lay-id="'+chlID+'"]');
		/*if(chlID==2){

		}*/
		if (isActive.length>0){
			//element.tabChange('menuTab',chlID);
			element.tabDelete('menuTab',chlID);
			element.tabAdd('menuTab',{
				title: $(chl).attr('data-title'),//标题
				content:'<iframe frameborder="0" style="width: 100%; height: calc(100vh - 157px)" name="'+$(chl).attr('data-title')+'" src="'+$(chl).attr('data-url')+'"></iframe>',//内容
				id:chlID
			});
			element.tabChange('menuTab',chlID);	//添加完成切换到该选项卡
		}else {
			element.tabAdd('menuTab',{
				title: $(chl).attr('data-title'),//标题
				content:'<iframe frameborder="0" style="width: 100%; height: calc(100vh - 157px)" name="'+$(chl).attr('data-title')+'" src="'+$(chl).attr('data-url')+'"></iframe>',//内容
				id:chlID
			});
			element.tabChange('menuTab',chlID);	//添加完成切换到该选项卡
		}
		if($(window).width()<900){
			lock = false;
			$("#menuList").animate({left: "-100%"});
		}
	});
	//把tab选项卡第一个的删除按钮隐藏掉
	$(".tabList").children('li').first().children('.layui-tab-close').css("display","none");
	//tab触发事件
	$(".menuCli").click(function(){
		var id = $(this).attr('data-id');//获取id
		var url = $(this).attr('data-url');
		var title = $(this).attr('data-title');//获取标题
		var isActive = $(".layui-tab-title").find("li[lay-id=" + id + "]");
		if(isActive.length>0){
			if (id!=0 && id!=29)
			{
				element.tabDelete('menuTab',id);
				element.tabAdd('menuTab',{
					title: title,//标题
					content:'<iframe frameborder="0" style="width: 100%; height: calc(100vh - 157px)" name="'+title+'" src="'+url+'"></iframe>',//内容
					id:id
				});
				element.tabChange('menuTab',id);	//添加完成切换到该选项卡
			}
			else
			{
				element.tabChange('menuTab',id);
			}
		}else{
			element.tabAdd('menuTab',{
				/*<iframe id="index" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" name="iframe0" width="100%" height="100%" src="{{url('/admin/home')}}"></iframe>*/
				title: title,//标题
				content:'<iframe frameborder="0" style="width: 100%; height: calc(100vh - 157px)" name="'+title+'" src="'+url+'"></iframe>',//内容
				id:id
			});
			element.tabChange('menuTab',id);	//添加完成切换到该选项卡
		}

	});
	$('.downCode').click(function () {
		$("#down").html('0')
		var id = $(this).attr('data-id');
		var title = $(this).attr('data-title');
		var url = $(this).attr('data-url');
		var isActive =$('.layui-tab-title').find('li[lay-id="'+id+'"]');
		if (isActive.length>0){
			element.tabChange('menuTab',id);
		}else {
			element.tabAdd('menuTab',{
				title:title,//标题
				content:'<iframe frameborder="0" style="width: 100%; height: calc(100vh - 157px)" name="'+title+'" src="'+url+'"></iframe>',
				id:id
			});
			element.tabChange('menuTab',id);
		}
	});
	//修改密码
	$('.addBtn').click(function () {
		var url = $(this).attr('data-url');
		layer.open({
			type:2,
			title:'修改密码',
			shadeClose: true,
			offset:'10%',
			area:['60%','80%'],
			id:'update_password',
			content:url
		});
	});

	//下分请求
	$("#downCode").click(function () {

	});

	//菜单
	$("#menuTxt").click(function () {
		if(!lock){
			$("#menuList").animate({left:"0%"});
		}else{
			$("#menuList").animate({left:"-100%"});
		}
		lock = !lock;
	})

	$(window).resize(function () {
		reSize();
	});
	reSize();
	function reSize(){
		var oWid = $(window).width();
		if(oWid < 900){
			$("#menuTxt").css('display','inline-block');
			$("#menuList").addClass('menuBox');
			$("#menuList").css('left',"-100%");

		}else{
			$("#menuTxt").css('display','none');
			$("#menuList").removeClass('menuBox');
			$("#menuList").css('left',"0%");
		}
	}
	function tabAdd(url,id,title) {
		var isActive = $(".layui-tab-title").find("li[lay-id="+id+"]");
		if(isActive.length>0){
			element.tabChange('menuTab',id)	//切换到指定选项卡
		}else{
			element.tabAdd('menuTab',{
				/*<iframe id="index" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" name="iframe0" width="100%" height="100%" src="{{url('/admin/home')}}"></iframe>*/
				title: title,//标题
				content:'<iframe frameborder="0" style="width: 100%; height: calc(100vh - 157px)" name="'+title+'" src="'+url+'"></iframe>',//内容
				id:id
			});
			element.tabChange('menuTab',id);	//添加完成切换到该选项卡
		}
	}
});