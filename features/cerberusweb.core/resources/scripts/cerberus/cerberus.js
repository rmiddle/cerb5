<!--
// ***********
function CreateKeyHandler(cb) {
//	if(is_ie) {
//		func = window.eval("blank=function(e) {return window.cb(e);}");
//	} else {
//		func =  function(e) {return window.cb(e);}
//	}

	if(window.Event) {
		document.captureEvents(Event.KEYDOWN);
	}
	
	document.onkeydown = cb;
}

function getKeyboardKey(evt,as_code) {
	var browser=navigator.userAgent.toLowerCase();
	var is_ie=(browser.indexOf("msie")!=-1 && document.all);
	
	  if(window.Event) {
	  	if(evt.altKey || evt.metaKey || evt.ctrlKey) {
	  		return;
	  	}
	    mykey = evt.which;
	  }
	  else if(event) {
	  	evt = event;
	  	if((evt.modifiers & event.ALT_MASK) || (evt.modifiers & event.CTRL_MASK)) {
			return;
		}
	  	if(evt.altKey || evt.metaKey || evt.ctrlKey) { // new style
	  		return;
	  	}
   		mykey = evt.keyCode
	  }
	  
	  mychar = String.fromCharCode(mykey);
	  
	var src=null;
	
	try {
		if(evt.srcElement) src=evt.srcElement;
		else if(evt.target) src=evt.target;
	}
	catch(e) {}

	if(null == src) {
		return;
	}
  
	for(var element=src;element!=null;element=element.parentNode) {
		var nodename=element.nodeName;
		if(nodename=="TEXTAREA"	
			|| (nodename=="SELECT")
			|| (nodename=="INPUT") //  && element.type != "checkbox"
			|| (nodename=="BUTTON")
			)
			{ return; }
	}
	
	return (null==as_code||!as_code) ? mychar : mykey;
}

// ***********

function appendFileInput(divName,fieldName) {
	var frm = document.getElementById(divName);
	if(null == frm) return;

	// Why is IE such a PITA?  it doesn't allow post-creation specification of the "name" attribute.  Who thought that one up?
	try {
		var fileInput = document.createElement('<input type="file" name="'+fieldName+'" size="45">');
	} catch (err) {
		var fileInput = document.createElement('input');
		fileInput.setAttribute('type','file');
		fileInput.setAttribute('name',fieldName);
		fileInput.setAttribute('size','45');
	}
	
	// Gotta add the <br> as a child, see below
	var brTag = document.createElement('br');
	
	frm.appendChild(fileInput);
	frm.appendChild(brTag);

	// This is effectively the same as frm.innerHTML = frm.innerHTML + "<br>".
	// The innerHTML element doesn't know jack about the selected files of the child elements, so it throws that away.	
	//frm.innerHTML += "<BR>";
}

var cAjaxCalls = function() {

	this.showBatchPanel = function(view_id,target) {
		var viewForm = document.getElementById('viewForm'+view_id);
		if(null == viewForm) return;
		var elements = viewForm.elements['ticket_id[]'];
		if(null == elements) return;

		var len = elements.length;
		var ids = new Array();

		if(null == len && null != elements.value) {
			ids[0] = elements.value;
		} else {
			for(var x=len-1;x>=0;x--) {
				if(elements[x].checked) {
					//frm.appendChild(elements[x]);
					ids[ids.length] = elements[x].value;
				}
			}
		}
		
		var ticket_ids = ids.join(','); // [TODO] Encode?
	
		genericAjaxPanel('c=tickets&a=showBatchPanel&view_id=' + view_id + '&ids=' + ticket_ids,target,false,'500px');
	}

	this.saveBatchPanel = function(view_id) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewDiv = document.getElementById(divName);
		var viewForm = document.getElementById(formName);
		if(null == viewForm || null == viewDiv) return;

		var frm = document.getElementById('formBatchUpdate');
		var elements = viewForm.elements['ticket_id[]'];
		if(null == elements) return;
		
		var len = elements.length;
		var ids = new Array();
		
		if(null == len && null != elements.value) {
			ids[0] = elements.value;
		} else {
			for(var x=len-1;x>=0;x--) {
				if(elements[x].checked) {
					ids[ids.length] = elements[x].value;
				}
			}
		}
		
		frm.ticket_ids.value = ids.join(',');		

		showLoadingPanel();

		genericAjaxPost('formBatchUpdate', '', 'c=tickets&a=doBatchUpdate', function(o) {
			viewDiv.innerHTML = o.responseText;

			if(null != genericPanel) {
				genericPanel.hide();
			}
			
			document.location = '#top';
			genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
			
			hideLoadingPanel();
		});
	}

	this.showAddressBatchPanel = function(view_id,target) {
		var viewForm = document.getElementById('viewForm'+view_id);
		if(null == viewForm) return;
		var elements = viewForm.elements['row_id[]'];
		if(null == elements) return;

		var len = elements.length;
		var ids = new Array();

		if(null == len && null != elements.value) {
			ids[0] = elements.value;
		} else {
			for(var x=len-1;x>=0;x--) {
				if(elements[x].checked) {
					//frm.appendChild(elements[x]);
					ids[ids.length] = elements[x].value;
				}
			}
		}
		
		var row_ids = ids.join(','); // [TODO] Encode?
	
		genericAjaxPanel('c=contacts&a=showAddressBatchPanel&view_id=' + view_id + '&ids=' + row_ids,target,false,'500px',this.cbAddressPeek);
	}
	
	this.saveAddressBatchPanel = function(view_id) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewDiv = document.getElementById(divName);
		var viewForm = document.getElementById(formName);
		if(null == viewForm || null == viewDiv) return;

		var frm = document.getElementById('formBatchUpdate');

		var elements = viewForm.elements['row_id[]'];
		if(null == elements) return;
		
		var len = elements.length;
		var ids = new Array();
		
		if(null == len && null != elements.value) {
			ids[0] = elements.value;
		} else {
			for(var x=len-1;x>=0;x--) {
				if(elements[x].checked) {
					ids[ids.length] = elements[x].value;
				}
			}
		}
		
		frm.address_ids.value = ids.join(',');		

		genericAjaxPost('formBatchUpdate', '', 'c=contacts&a=doAddressBatchUpdate', function(o) {
			viewDiv.innerHTML = o.responseText;

			if(null != genericPanel) {
				genericPanel.hide();
			}
			
			document.location = '#top';
			//genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
		});
	}

	/*
	this.showTemplatesPanel = function(txt_name,msgid) {
		var div = document.getElementById(txt_name);
		if(null == div) return;

		genericAjaxPanel('c=display&a=showTemplatesPanel&reply_id='+msgid+'&txt_name='+txt_name,null,false,'550px',function(o) {
			var tabView = new YAHOO.widget.TabView();
			
			tabView.addTab( new YAHOO.widget.Tab({
			    label: 'List',
			    dataSrc: DevblocksAppPath+'ajax.php?c=display&a=showTemplateList&reply_id='+msgid+'&txt_name='+txt_name,
			    cacheData: true,
			    active: true
			}));
			
			tabView.appendTo('templatePanelOptions');
			
			div.content.focus();
		});
	}
	*/

	this.insertReplyTemplate = function(template_id,txt_name,msgid) {
		var cObj = YAHOO.util.Connect.asyncRequest('GET', DevblocksAppPath+'ajax.php?c=display&a=getTemplate&id=' + template_id + '&reply_id='+msgid, {
				success: function(o) {
					var caller = o.argument.caller;
					var id = o.argument.msgid;
					var txt_name = o.argument.txt_name;
					var template_id = o.argument.template_id;
					
					var div = document.getElementById(txt_name);
					if(null == div) return;
					
					insertAtCursor(div, o.responseText);
					div.focus();
					
					try {
						genericPanel.hide();
					} catch(e) {}
				},
				failure: function(o) {},
				argument:{caller:this,msgid:msgid,txt_name:txt_name,template_id:template_id}
		});
	}

	this.viewMoveTickets = function(view_id) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewDiv = document.getElementById(divName);
		var viewForm = document.getElementById(formName);
		if(null == viewForm || null == viewDiv) return;

		genericAjaxPost(formName, '', 'c=tickets&a=viewMoveTickets&view_id='+view_id, function(o) {
			viewDiv.innerHTML = o.responseText;
			genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
		});
	}

	this.viewTicketsAction = function(view_id,action) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewDiv = document.getElementById(divName);
		var viewForm = document.getElementById(formName);
		if(null == viewForm || null == viewDiv) return;

		showLoadingPanel();

		switch(action) {
			case 'merge':
				genericAjaxPost(formName, '', 'c=tickets&a=viewMergeTickets&view_id='+view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 'not_spam':
				genericAjaxPost(formName, '', 'c=tickets&a=viewNotSpamTickets&view_id='+view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 'take':
				genericAjaxPost(formName, '', 'c=tickets&a=viewTakeTickets&view_id='+view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 'surrender':
				genericAjaxPost(formName, '', 'c=tickets&a=viewSurrenderTickets&view_id='+view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 'waiting':
				genericAjaxPost(formName, '', 'c=tickets&a=viewWaitingTickets&view_id='+view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 'not_waiting':
				genericAjaxPost(formName, '', 'c=tickets&a=viewNotWaitingTickets&view_id='+view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			default:
				hideLoadingPanel();
				break;
		}
	}
	
	this.viewCloseTickets = function(view_id,mode) {
		var divName = 'view'+view_id;
		var formName = 'viewForm'+view_id;
		var viewDiv = document.getElementById(divName);
		var viewForm = document.getElementById(formName);
		if(null == viewForm || null == viewDiv) return;

		showLoadingPanel();

		switch(mode) {
			case 1: // spam
				genericAjaxPost(formName, '', 'c=tickets&a=viewSpamTickets&view_id=' + view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			case 2: // delete
				genericAjaxPost(formName, '', 'c=tickets&a=viewDeleteTickets&view_id=' + view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
			default: // close
				genericAjaxPost(formName, '', 'c=tickets&a=viewCloseTickets&view_id=' + view_id, function(o) {
					viewDiv.innerHTML = o.responseText;
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
					hideLoadingPanel();
				});
				break;
		}
	}
	
	this.postAndReloadView = function(frm,view_id) {
		YAHOO.util.Connect.setForm(frm);
		
		var div = document.getElementById(view_id);
		if(null == div) return;
		
		var anim = new YAHOO.util.Anim(div, { opacity: { to: 0.2 } }, 1, YAHOO.util.Easing.easeOut);
		anim.animate();
		
		var cObj = YAHOO.util.Connect.asyncRequest('POST', DevblocksAppPath+'ajax.php', {
				success: function(o) {
					var div = document.getElementById(o.argument.view_id);
					if(null == div) return;

					div.innerHTML = o.responseText;
					genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');					

					var anim = new YAHOO.util.Anim(div, { opacity: { to: 1.0 } }, 1, YAHOO.util.Easing.easeOut);
					anim.animate();

					if(null != genericPanel) {
						try {
							genericPanel.destroy();
							genericPanel = null;
						} catch(e) {}
					}
					
				},
				failure: function(o) {},
				argument:{frm:frm,view_id:view_id}
		});
		
		YAHOO.util.Connect.setForm(0);
	}
	
	this.viewUndo = function(view_id) {
		var viewDiv = document.getElementById('view'+view_id);
		if(null == viewDiv) return;
	
		genericAjaxGet('','c=tickets&a=viewUndo&view_id=' + view_id,
			function(o) {
				viewDiv.innerHTML = o.responseText;
				genericAjaxGet('viewSidebar'+view_id,'c=tickets&a=refreshSidebar');
			}
		);		
	}

	this.cbEmailSinglePeek = function(o) {
		this._cbEmailPeek(1,o);
	}
	
	this.cbEmailMultiplePeek = function(o) {
		this._cbEmailPeek(null,o);
	}

	this._cbEmailPeek = function(mode,o) {
		var myDataSource = new YAHOO.widget.DS_XHR(DevblocksAppPath+"ajax.php", ["\n", "\t"] );
		myDataSource.scriptQueryAppend = "c=contacts&a=getEmailAutoCompletions"; 
	
		myDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_FLAT;
		myDataSource.maxCacheEntries = 60;
		myDataSource.queryMatchSubset = true;
		myDataSource.connTimeout = 3000;
	
	 	var myInput = document.getElementById('emailinput'); 
	    var myContainer = document.getElementById('emailcontainer'); 
	
		var myAutoComp = new YAHOO.widget.AutoComplete(myInput,myContainer, myDataSource);
		
		if(null == mode || !mode)
			myAutoComp.delimChar = ",";
		
		myAutoComp.queryDelay = 1;
		//myAutoComp.useIFrame = true; 
		myAutoComp.typeAhead = false;
		myAutoComp.useShadow = true;
		//myAutoComp.prehighlightClassName = "yui-ac-prehighlight"; 
		myAutoComp.allowBrowserAutocomplete = false;
	
		myAutoComp.formatResult = function(aResultItem, sQuery) {
		   var sKey = aResultItem[1];
		   sKey = sKey.replace('<','&lt;');
		   sKey = sKey.replace('>','&gt;');
		   
		   var aMarkup = ["<div id='ysearchresult'>",
		      sKey,
		      "</div>"];
		  return (aMarkup.join(""));
		};
	}

	this.cbAddressPeek = function(o) {
		var myDataSource = new YAHOO.widget.DS_XHR(DevblocksAppPath+"ajax.php", ["\n", "\t"] );
		myDataSource.scriptQueryAppend = "c=contacts&a=getOrgsAutoCompletions"; 
	
		myDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_FLAT;
		myDataSource.maxCacheEntries = 60;
		myDataSource.queryMatchSubset = true;
		myDataSource.connTimeout = 3000;
	
	 	var myInput = document.getElementById('contactinput'); 
	    var myContainer = document.getElementById('contactcontainer'); 
	
		var myAutoComp = new YAHOO.widget.AutoComplete(myInput,myContainer, myDataSource);
		// myAutoComp.delimChar = ",";
		myAutoComp.queryDelay = 1;
		//myAutoComp.useIFrame = true; 
		myAutoComp.typeAhead = false;
		myAutoComp.useShadow = true;
		//myAutoComp.prehighlightClassName = "yui-ac-prehighlight"; 
		myAutoComp.allowBrowserAutocomplete = false;

		/*
		var contactOrgAutoCompSelected = function contactOrgAutoCompSelected(sType, args, me) {
			org_str = new String(args[2]);
			org_arr = org_str.split(',');
			myInput.value=org_arr[1];
		};
		
		obj=new Object();
		myAutoComp.itemSelectEvent.subscribe(contactOrgAutoCompSelected, obj);
		*/
	}

	this.cbOrgCountryPeek = function(o) {
		var myDataSource = new YAHOO.widget.DS_XHR(DevblocksAppPath+"ajax.php", ["\n", "\t"] );
		myDataSource.scriptQueryAppend = "c=contacts&a=getCountryAutoCompletions"; 
	
		myDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_FLAT;
		myDataSource.maxCacheEntries = 60;
		myDataSource.queryMatchSubset = true;
		myDataSource.connTimeout = 3000;
	
	 	var myInput = document.getElementById('org_country_input'); 
	    var myContainer = document.getElementById('org_country_container'); 
	
		var myAutoComp = new YAHOO.widget.AutoComplete(myInput,myContainer, myDataSource);
		// myAutoComp.delimChar = ",";
		myAutoComp.queryDelay = 1;
		//myAutoComp.useIFrame = true; 
		myAutoComp.typeAhead = false;
		myAutoComp.useShadow = true;
		//myAutoComp.prehighlightClassName = "yui-ac-prehighlight"; 
		myAutoComp.allowBrowserAutocomplete = false;
	}

	this.getDateChooser = function(div,field) {
		var cal = new YAHOO.widget.Calendar("calChooser", div);
		cal.cfg.setProperty("close",true); 
		cal.selectEvent.subscribe(function(type,args,obj) {
			var dates = args[0];
			var date = dates[0];
			var calDate = date[1] + '/' + date[2] + '/' + date[0];
			field.value = calDate;
			cal.hide();
		}, cal, true);
		cal.render();
		cal.show();
	}
}

var ajax = new cAjaxCalls();
-->