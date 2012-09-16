//showItem需要显示的HTML-id
//classItem需要变化class的 HTML-id
//cname:class名 
//n需显示的列 colnum全部列数
function ChangIterm(showItem,classItem,cname,n,colnum) {
	for(var i=1;i<=colnum;i++){
		var curC=document.getElementById(showItem+i);
		var curB=document.getElementById(classItem+i);
		if(n==i){
			curC.style.display="block";
			curB.className=cname;
		}else{
			curC.style.display="none";
			curB.className=""
		}
	}
} 