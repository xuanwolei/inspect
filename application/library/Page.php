<?php
/**
 * 分页类
 * date：2015-3-25
 * author：袁宝城
 */
class Page {
	private $total;    //数据表中总记录数
	private $listRows; //每页显示行数
	private $limit;    //SQL语句使用limit从名
	public  $uri;      //url地址
	private $pageNum;  //页数
	public  $page;
	//在分页信息中显示内容，可以自己设置
	private $config=array('head'=>"条记录", "prev"=>"上一页", "next"=>"下一页", "first"=>"首页", "last"=>"末页");
	private $listNum=10; //默认分页列表显示的个数

	/**
	 * 构造方法，可以设置分页类的属性
	 * @param	int	$total		计算分页的总记录数
	 * @param	int	$listRows	可选的，默认每页需要显示的记录数
	 * @param	string	$pa		可选的，为向目标页面传递参数
	 * @param 	bool	$ord		可选的，默认值为true, 如果为true默认页为第一页，false则为最后一页
	 */
	public function __construct($total, $listRows=20, $pa="", $ord=true){
		$this->total=$total;
		$this->listRows=$listRows;

		$this->uri=$this->getUri($pa);
		$this->pageNum=ceil($this->total/$this->listRows);
		if(!empty($_GET["page"])) {
			$page=$_GET["page"];
		}else{
			if($ord)
				$page=1;
			else
				$page=$this->pageNum;
		}

		if($total > 0) {
			if(preg_match('/\D/', $page) ){
				$this->page=1;
			}else{
				$this->page=$page;
			}
		}else{
			$this->page=0;
		}
		
		$this->limit=$this->setLimit();
	}

	/**
	 * 用于设置显示分页的信息，可以连贯操作
	 * @param	string	$param	是数组config的下标
	 * @param	string	$value	用于设置config下标对应的元素值
	 * @return	object		返回本对象自己$this
	 */
	function set($param, $value){
		if(array_key_exists($param, $this->config)){
			$this->config[$param]=$value;
		}
		return $this;
	}

	private function setLimit(){
		
		if($this->page > 0)
			return ($this->page-1)*$this->listRows.", {$this->listRows}";
		else
			return 0;
	}

	private function getUri($query=''){
	
	$url=$_SERVER['PHP_SELF']."?";
	$action = empty($_GET['c'])?"m={$_GET['m']}":"m={$_GET['m']}&c={$_GET['c']}";
	$query = !empty($query)?"&".$query:'';
	$url .= $action.$query;


	if(substr($url,-1)!="&")
		$url.="&";
       return $url;
	}

	function __get($args){
		if($args=="limit" || $args=="page" || $args=="uri")
			return $this->$args;
		else
		return null;
	}

	private function start(){
		if($this->total==0)
			return 0;
		else
			return ($this->page-1)*$this->listRows+1;
	}

	private function end(){
		return min($this->page*$this->listRows,$this->total);
	}

	private function firstprev(){
		$str = '';
		if ($this->pageNum > 1) {
			//第一页的情况
			$style = "style='display:inline-block;border:1px solid #ccc;background:#fff;color:#ccc;padding:6px 11px;'";
			$str="&nbsp;<a  {$style} page=1'>{$this->config["first"]}</a>";
			$str.="&nbsp;<a {$style} page=".($this->page-1)."'>{$this->config["prev"]}</a>&nbsp;";		
		}
		
		//非第一页的情况
		if($this->page > 1) {
			$style = "style='display:inline-block;border:1px solid #ccc;background:#fff;color:#000;padding:6px 11px;'";
			$str="&nbsp;<a {$style} href='{$this->uri}page=1'>{$this->config["first"]}</a>";
			$str.="&nbsp;<a {$style} href='{$this->uri}page=".($this->page-1)."'>{$this->config["prev"]}</a>&nbsp;";		
		}
		
		return $str;
	}


	private function pageList(){
		$linkPage="&nbsp;<b>";
		
		$inum=floor($this->listNum/2);
	
		for($i=$inum; $i>=1; $i--){
			$page=$this->page-$i;

			if($page>=1)
				$linkPage.="<a onmouseout=\"this.style.background='#fff';this.style.color='#000';this.style.borderColor='#ccc';\" onmouseover=\"this.style.borderColor='#9CDDF5';this.style.color='#fff';this.style.background='#9CDDF5';\" style='color:#000;background:#fff;padding:6px 11px;display:inline-block;border-radius:4px;border:1px solid #ccc;' href='{$this->uri}page={$page}'>{$page}</a>&nbsp;";

		}

		if($this->pageNum > 1)
			$linkPage.="<span style='color:#fff;padding:6px 11px;display:inline-block;border-radius:4px;border:1px solid #00A2CA;background:#00A2CA;'>{$this->page}</span>&nbsp;";
		

		for($i=1; $i<=$inum; $i++){
			$page=$this->page+$i;
			if($page<=$this->pageNum)
				$linkPage.="<a onmouseout=\"this.style.background='#fff';this.style.color='#000';this.style.borderColor='#ccc';\" onmouseover=\"this.style.borderColor='#9CDDF5';this.style.color='#fff';this.style.background='#9CDDF5';\" style='color:#000;background:#fff;padding:6px 11px;display:inline-block;border-radius:4px;border:1px solid #ccc;' href='{$this->uri}page={$page}'>{$page}</a>&nbsp;";
			else
				break;
		}
		$linkPage.='</b>';
		return $linkPage;
	}

	private function nextlast(){
		$str = '';
		if ($this->pageNum > 1) {
			//第一页的情况
			$style = "style='display:inline-block;border:1px solid #ccc;background:#fff;color:#ccc;padding:6px 11px;'";
			$str="&nbsp;<a {$style}>{$this->config["next"]}</a>&nbsp;";
			$str.="&nbsp;<a {$style} >{$this->config["last"]}</a>&nbsp;";
		}

		if($this->page != $this->pageNum) {
			$style = "style='display:inline-block;border:1px solid #ccc;background:#fff;color:#000;padding:6px 11px;'";
			$str="&nbsp;<a {$style} href='{$this->uri}page=".($this->page+1)."'>{$this->config["next"]}</a>&nbsp;";
			$str.="&nbsp;<a {$style} href='{$this->uri}page=".($this->pageNum)."'>{$this->config["last"]}</a>&nbsp;";
		}

		return $str;

	}

	private function goPage(){
		if($this->pageNum > 1) {
			return '&nbsp;<input style="padding-left:10px;font-size:14px;padding:3px;width:20px;border:1px solid #CCCCCC;" type="text" onkeydown="javascript:if(event.keyCode==13){var page=(this.value>'.$this->pageNum.')?'.$this->pageNum.':this.value;location=\''.$this->uri.'page=\'+page+\'\'}" value="'.$this->page.'"><input onmouseout=\'this.style.background="#00A2CA";\' onmouseover=\'this.style.background="#9CDDF5";\' style="cursor:pointer;width:50px;height:25px;border:none;border-radius:4px;margin-left:5px;background:#00A2CA;color:#fff;"  type="button" value="GO" onclick="javascript:var page=(this.previousSibling.value>'.$this->pageNum.')?'.$this->pageNum.':this.previousSibling.value;location=\''.$this->uri.'page=\'+page+\'\'">&nbsp;';
		}
	}

	private function disnum(){
		if($this->total > 0){
			return $this->end()-$this->start()+1;
		}else{
			return 0;
		}
	}
	/**
	 * 按指定的格式输出分页
	 * @param	int	为0-7的数字，每个数字作为一个参数，可以自定义输出分页结构和调整结构的顺序
	 * @return	string	分页信息内容
	 */
	function fpage(){
		$arr=func_get_args();

		$html[0]="&nbsp;搜索到<b style='color:red;'> {$this->total} </b>{$this->config["head"]}&nbsp;";
		$html[1]="&nbsp;本页共 <b style='color:red;'>".$this->disnum()."</b> 条&nbsp;";
		$html[2]="&nbsp;本页从 <b style='color:red;'>{$this->start()}-{$this->end()}</b> 条&nbsp;";
		$html[3]="&nbsp;<b style='color:red;'>{$this->page}/{$this->pageNum}</b>页&nbsp;";
		$html[4]=$this->firstprev();
		$html[5]=$this->pageList();
		$html[6]=$this->nextlast();
		$html[7]=$this->goPage();

		$fpage='<div style="font:12px \'\5B8B\4F53\',san-serif;">';
		if(count($arr) < 1)
			$arr=array(4,5,6,7);				

		for($i=0; $i<count($arr); $i++)
			$fpage.=$html[$arr[$i]];
	
		$fpage.='</div>';
		return $fpage;
	}
}
