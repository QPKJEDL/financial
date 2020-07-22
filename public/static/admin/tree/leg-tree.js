((global) => {
	//添加的dom方法   $().xxx 或 leg().xxx
	$.fn.extend({
		uuid() {
			var s = [];
			var hexDigits = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
			for (var i = 0; i < 8; i++) {
				s[i] = hexDigits.substr(Math.floor(Math.random() * 0x30), 1);
			}
			var uuid = s.join("");
			return uuid;
		},
		tree(data, arrs) {
			var tree = this;

			$(tree).on("click", ".isShow", function() {
				let that = this;
				isShow(that);
			})

			function isShow(that) {
				//:visible 判断当前元素是否可见。
				if ($(that).parent().find("ul").is(":visible")) {
					$(that).parent().find("ul").hide();
					$(that).attr("style", 'transform:rotate(-90deg)');
				} else {
					if ($(that).parent().find("ul").length > 0) {
						$(that).attr("style", 'transform:rotate(0deg)');
					} else {
						$(that).attr("style", 'opacity: 0;');
					}
					$(that).parent().find("ul").show();
				}
			}
			//判断父级选中状态
			function checkParent(param) {
				if ($(param).is(':checked')) {
					$(param).parent().parent().prev().prev().prev().prev().prop("checked", 'true');
				} else {
					var temp = $(param).parent().parent().find("input");
					var isChecked = false;
					//判断子级是否有checked的，没有就取消父级的选中状态
					$.each(temp, function(index, item) {
						if (item.checked) {
							isChecked = true;
						}
					});

					if (!isChecked) {
						$(param).parent().parent().prev().prev().prev().prev().removeAttr("checked");
					}
				}
				var asd = $(param).parent().parent().prev().prev().prev().prev()[0];
				if (asd != undefined) {
					checkParent(asd)
				}
				return;
			}

			//判断子级选中状态
			function checkChildren(param) {
				if (param.checked) {
					$(param).next().next().next().next().children().find("input").prop("checked", 'true');
				} else {
					$(param).next().next().next().next().children().find("input").removeAttr("checked");
				}
			}

			$(tree).on("click", "input", function() {
				//先子级在父级避免出错（父级里面有判断子级是否选中的）
				if (data[0].cascade) {
					checkChildren(this);
					checkParent(this);
				}
			})

			//使点击a标签等同于点击 input
			$(tree).on("click", "a", function() {
				if ($(this).prev().prev().prev()[0].checked) {
					$(this).prev().prev().prev().removeAttr("checked");
				} else {
					$(this).prev().prev().prev().prop("checked", 'true');
				}
				//判断是否为子父级联
				if (data[0].cascade) {
					checkChildren($(this).prev().prev().prev()[0]);
					checkParent($(this).prev().prev().prev()[0]);
				}
			})

			//id相等就选中
			const insert = (children, arr) => {
				for (var a in arr) {
					if (children.id == arr[a]) {
						children.checked = true
					}
				}
			}

			//设置tree节点是否选中
			function setCheckedNodes(data, arrs) {
				for (let x in data) {
					let children = data[x].children;
					if (children != null) {
						for (let y in children) {
							insert(children[y], arrs)
							setCheckedNodes(children, arrs);
						}
					} else {
						return;
					}
				}

			}

			//递归
			var ids = 0;
			var uuid = $(this).uuid();
			function createTree(data) {
				var str = '<ul>';
				for (var i = 0; i < data.length; i++) {
					ids++;
					str +=
						'<li><img class="isShow" src="minus.png" />'
					if (data[i].checked == true) {
						str += '<input id="' + uuid+ids + '" type="checkbox" checked ' +
							'data-show="' + data[i].open + '" value="' + data[i].id + '"/>'
					} else {
						str += '<input id="' + uuid+ids + '" type="checkbox" ' +
							'data-show="' + data[i].open + '" value="' + data[i].id + '"/>'
					}
					str += '<label class="label" for="' +
						uuid+ids + '"/><i class="' + data[i].ico + '"/><a href="#">' + data[i].name + '</a>';
					if (data[i].children && data[i].children != '') {
						str += createTree(data[i].children);
					}
					str += '</li>';
				};
				str += '</ul>';
				return str;
			};

			//通过ID选中
			if (arrs.constructor == Array) { //判断是否为数组
				setCheckedNodes(data, arrs);
			}

			//把树放到容器
			$(tree).html(createTree(data));

			//通过原始数据选中
			$.each($("input:checkbox:checked"), function() {
				checkParent(this)
			});

			//是否展开
			$.each($("input"), function() {
				if (this.getAttribute('data-show') == 'false') {
					$(this).parent().find("ul").hide();
					$(this).prev()[0].setAttribute("style", 'transform:rotate(-90deg)');

				} else {
					$(this).parent().find("ul").show();
					if ($(this).parent().find("ul").length > 0) {
						$(this).prev()[0].setAttribute("style", 'transform:rotate(0deg)');
					} else {
						$(this).prev()[0].setAttribute("style", 'opacity: 0;');
					}
				}
			});
		},
	})

	//添加的$.xxx() 或者leg.xxx()
	$.extend({
		getCheckedNodes() { //获取选中id集合
			var arr = []
			$.each($('input:checkbox:checked'), function() {
				let temp = $(this).val();
				if (temp != "" && temp != "undefined") {
					arr.push($(this).val())
				}
			});
			return arr;
		}
	});

	global.leg = global.$ = $;

})(window)
