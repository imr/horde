CKEDITOR.dialog.add("select",function(t){function s(c,b,a,f,e){c=k(c);var d;if(f){d=f.createElement("OPTION")}else{d=document.createElement("OPTION")}if(c&&d&&d.getName()=="option"){if(CKEDITOR.env.ie){if(!isNaN(parseInt(e,10))){c.$.options.add(d.$,e)}else{c.$.options.add(d.$)}d.$.innerHTML=b.length>0?b:"";d.$.value=a}else{if(e!==null&&e<c.getChildCount()){c.getChild(e<0?0:e).insertBeforeMe(d)}else{c.append(d)}d.setText(b.length>0?b:"");d.setValue(a)}}else{return false}return d}function r(c){c=k(c);var b=n(c);for(var a=c.getChildren().count()-1;a>=0;a--){if(c.getChild(a).$.selected){c.getChild(a).remove()}}m(c,b)}function q(c,b,a,e){c=k(c);if(b<0){return false}var d=c.getChild(b);d.setText(a);d.setValue(e);return d}function p(a){a=k(a);while(a.getChild(0)&&a.getChild(0).remove()){}}function o(c,b,a){c=k(c);var h=n(c);if(h<0){return false}var g=h+b;g=g<0?0:g;g=g>=c.getChildCount()?c.getChildCount()-1:g;if(h==g){return false}var f=c.getChild(h),e=f.getText(),d=f.getValue();f.remove();f=s(c,e,d,!a?null:a,g);m(c,g);return f}function n(a){a=k(a);return a?a.$.selectedIndex:-1}function m(c,b){c=k(c);if(b<0){return null}var a=c.getChildren().count();c.$.selectedIndex=b>=a?a-1:b;return c}function l(a){a=k(a);return a?a.getChildren():false}function k(a){if(a&&a.domId&&a.getInputElement().$){return a.getInputElement()}else{if(a&&a.$){return a}}return false}return{title:t.lang.select.title,minWidth:CKEDITOR.env.ie?460:395,minHeight:CKEDITOR.env.ie?320:300,onShow:function(){var d=this;delete d.selectBox;d.setupContent("clear");var c=d.getParentEditor().getSelection().getSelectedElement();if(c&&c.getName()=="select"){d.selectBox=c;d.setupContent(c.getName(),c);var b=l(c);for(var a=0;a<b.count();a++){d.setupContent("option",b.getItem(a))}}},onOk:function(){var c=this.getParentEditor(),b=this.selectBox,a=!b;if(a){b=c.document.createElement("select")}this.commitContent(b);if(a){c.insertElement(b);if(CKEDITOR.env.ie){var e=c.getSelection(),d=e.createBookmarks();setTimeout(function(){e.selectBookmarks(d)},0)}}},contents:[{id:"info",label:t.lang.select.selectInfo,title:t.lang.select.selectInfo,accessKey:"",elements:[{id:"txtName",type:"text",widths:["25%","75%"],labelLayout:"horizontal",label:t.lang.common.name,"default":"",accessKey:"N",style:"width:350px",setup:function(b,a){if(b=="clear"){this.setValue(this["default"]||"")}else{if(b=="select"){this.setValue(a.data("cke-saved-name")||a.getAttribute("name")||"")}}},commit:function(a){if(this.getValue()){a.data("cke-saved-name",this.getValue())}else{a.data("cke-saved-name",false);a.removeAttribute("name")}}},{id:"txtValue",type:"text",widths:["25%","75%"],labelLayout:"horizontal",label:t.lang.select.value,style:"width:350px","default":"",className:"cke_disabled",onLoad:function(){this.getInputElement().setAttribute("readOnly",true)},setup:function(b,a){if(b=="clear"){this.setValue("")}else{if(b=="option"&&a.getAttribute("selected")){this.setValue(a.$.value)}}}},{type:"hbox",widths:["175px","170px"],children:[{id:"txtSize",type:"text",labelLayout:"horizontal",label:t.lang.select.size,"default":"",accessKey:"S",style:"width:175px",validate:function(){var a=CKEDITOR.dialog.validate.integer(t.lang.common.validateNumberFailed);return this.getValue()===""||a.apply(this)},setup:function(b,a){if(b=="select"){this.setValue(a.getAttribute("size")||"")}if(CKEDITOR.env.webkit){this.getInputElement().setStyle("width","86px")}},commit:function(a){if(this.getValue()){a.setAttribute("size",this.getValue())}else{a.removeAttribute("size")}}},{type:"html",html:"<span>"+CKEDITOR.tools.htmlEncode(t.lang.select.lines)+"</span>"}]},{type:"html",html:"<span>"+CKEDITOR.tools.htmlEncode(t.lang.select.opAvail)+"</span>"},{type:"hbox",widths:["115px","115px","100px"],children:[{type:"vbox",children:[{id:"txtOptName",type:"text",label:t.lang.select.opText,style:"width:115px",setup:function(b,a){if(b=="clear"){this.setValue("")}}},{type:"select",id:"cmbName",label:"",title:"",size:5,style:"width:115px;height:75px",items:[],onChange:function(){var c=this.getDialog(),b=c.getContentElement("info","cmbValue"),a=c.getContentElement("info","txtOptName"),e=c.getContentElement("info","txtOptValue"),d=n(this);m(b,d);a.setValue(this.getValue());e.setValue(b.getValue())},setup:function(b,a){if(b=="clear"){p(this)}else{if(b=="option"){s(this,a.getText(),a.getText(),this.getDialog().getParentEditor().document)}}},commit:function(c){var b=this.getDialog(),a=l(this),g=l(b.getContentElement("info","cmbValue")),f=b.getContentElement("info","txtValue").getValue();p(c);for(var e=0;e<a.count();e++){var d=s(c,a.getItem(e).getValue(),g.getItem(e).getValue(),b.getParentEditor().document);if(g.getItem(e).getValue()==f){d.setAttribute("selected","selected");d.selected=true}}}}]},{type:"vbox",children:[{id:"txtOptValue",type:"text",label:t.lang.select.opValue,style:"width:115px",setup:function(b,a){if(b=="clear"){this.setValue("")}}},{type:"select",id:"cmbValue",label:"",size:5,style:"width:115px;height:75px",items:[],onChange:function(){var c=this.getDialog(),b=c.getContentElement("info","cmbName"),a=c.getContentElement("info","txtOptName"),e=c.getContentElement("info","txtOptValue"),d=n(this);m(b,d);a.setValue(b.getValue());e.setValue(this.getValue())},setup:function(c,b){var d=this;if(c=="clear"){p(d)}else{if(c=="option"){var a=b.getValue();s(d,a,a,d.getDialog().getParentEditor().document);if(b.getAttribute("selected")=="selected"){d.getDialog().getContentElement("info","txtValue").setValue(a)}}}}}]},{type:"vbox",padding:5,children:[{type:"button",id:"btnAdd",style:"",label:t.lang.select.btnAdd,title:t.lang.select.btnAdd,style:"width:100%;",onClick:function(){var c=this.getDialog(),b=c.getParentEditor(),a=c.getContentElement("info","txtOptName"),f=c.getContentElement("info","txtOptValue"),e=c.getContentElement("info","cmbName"),d=c.getContentElement("info","cmbValue");s(e,a.getValue(),a.getValue(),c.getParentEditor().document);s(d,f.getValue(),f.getValue(),c.getParentEditor().document);a.setValue("");f.setValue("")}},{type:"button",id:"btnModify",label:t.lang.select.btnModify,title:t.lang.select.btnModify,style:"width:100%;",onClick:function(){var c=this.getDialog(),b=c.getContentElement("info","txtOptName"),a=c.getContentElement("info","txtOptValue"),f=c.getContentElement("info","cmbName"),e=c.getContentElement("info","cmbValue"),d=n(f);if(d>=0){q(f,d,b.getValue(),b.getValue());q(e,d,a.getValue(),a.getValue())}}},{type:"button",id:"btnUp",style:"width:100%;",label:t.lang.select.btnUp,title:t.lang.select.btnUp,onClick:function(){var c=this.getDialog(),b=c.getContentElement("info","cmbName"),a=c.getContentElement("info","cmbValue");o(b,-1,c.getParentEditor().document);o(a,-1,c.getParentEditor().document)}},{type:"button",id:"btnDown",style:"width:100%;",label:t.lang.select.btnDown,title:t.lang.select.btnDown,onClick:function(){var c=this.getDialog(),b=c.getContentElement("info","cmbName"),a=c.getContentElement("info","cmbValue");o(b,1,c.getParentEditor().document);o(a,1,c.getParentEditor().document)}}]}]},{type:"hbox",widths:["40%","20%","40%"],children:[{type:"button",id:"btnSetValue",label:t.lang.select.btnSetValue,title:t.lang.select.btnSetValue,onClick:function(){var c=this.getDialog(),b=c.getContentElement("info","cmbValue"),a=c.getContentElement("info","txtValue");a.setValue(b.getValue())}},{type:"button",id:"btnDelete",label:t.lang.select.btnDelete,title:t.lang.select.btnDelete,onClick:function(){var c=this.getDialog(),b=c.getContentElement("info","cmbName"),a=c.getContentElement("info","cmbValue"),e=c.getContentElement("info","txtOptName"),d=c.getContentElement("info","txtOptValue");r(b);r(a);e.setValue("");d.setValue("")}},{id:"chkMulti",type:"checkbox",label:t.lang.select.chkMulti,"default":"",accessKey:"M",value:"checked",setup:function(b,a){if(b=="select"){this.setValue(a.getAttribute("multiple"))}if(CKEDITOR.env.webkit){this.getElement().getParent().setStyle("vertical-align","middle")}},commit:function(a){if(this.getValue()){a.setAttribute("multiple",this.getValue())}else{a.removeAttribute("multiple")}}}]}]}]}});