(function(){var j;function i(a){return a.type==CKEDITOR.NODE_TEXT&&a.getLength()>0&&(!j||!a.isReadOnly())}function p(a){return !(a.type==CKEDITOR.NODE_ELEMENT&&a.isBlockBoundary(CKEDITOR.tools.extend({},CKEDITOR.dtd.$empty,CKEDITOR.dtd.$nonEditable)))}var o=function(){var a=this;return{textNode:a.textNode,offset:a.offset,character:a.textNode?a.textNode.getText().charAt(a.offset):null,hitMatchBoundary:a._.matchBoundary}},n=["find","replace"],m=[["txtFindFind","txtFindReplace"],["txtFindCaseChk","txtReplaceCaseChk"],["txtFindWordChk","txtReplaceWordChk"],["txtFindCyclic","txtReplaceCyclic"]];function l(e){var d,c,b,a;d=e==="find"?1:0;c=1-d;var g,f=m.length;for(g=0;g<f;g++){b=this.getContentElement(n[d],m[g][d]);a=this.getContentElement(n[c],m[g][c]);a.setValue(b.getValue())}}var k=function(z,y){var h=new CKEDITOR.style(CKEDITOR.tools.extend({attributes:{"data-cke-highlight":1},fullMatch:1,ignoreReadonly:1,childRule:function(){return 0}},z.config.find_highlight,true)),g=function(t,r){var q=this,s=new CKEDITOR.dom.walker(t);s.guard=r?p:function(u){!p(u)&&(q._.matchBoundary=true)};s.evaluator=i;s.breakOnFalse=1;if(t.startContainer.type==CKEDITOR.NODE_TEXT){this.textNode=t.startContainer;this.offset=t.startOffset-1}this._={matchWord:r,walker:s,matchBoundary:false}};g.prototype={next:function(){return this.move()},back:function(){return this.move(true)},move:function(s){var q=this;var r=q.textNode;if(r===null){return o.call(q)}q._.matchBoundary=false;if(r&&s&&q.offset>0){q.offset--;return o.call(q)}else{if(r&&q.offset<r.getLength()-1){q.offset++;return o.call(q)}else{r=null;while(!r){r=q._.walker[s?"previous":"next"].call(q._.walker);if(q._.matchWord&&!r||q._.walker._.end){break}}q.textNode=r;if(r){q.offset=s?r.getLength()-1:0}else{q.offset=0}}}return o.call(q)}};var f=function(r,q){this._={walker:r,cursors:[],rangeLength:q,highlightRange:null,isMatched:0}};f.prototype={toDomRange:function(){var u=new CKEDITOR.dom.range(z.document),s=this._.cursors;if(s.length<1){var q=this._.walker.textNode;if(q){u.setStartAfter(q)}else{return null}}else{var t=s[0],r=s[s.length-1];u.setStart(t.textNode,t.offset);u.setEnd(r.textNode,r.offset+1)}return u},updateFromDomRange:function(t){var s=this;var r,q=new g(t);s._.cursors=[];do{r=q.next();if(r.character){s._.cursors.push(r)}}while(r.character);s._.rangeLength=s._.cursors.length},setMatched:function(){this._.isMatched=true},clearMatched:function(){this._.isMatched=false},isMatched:function(){return this._.isMatched},highlight:function(){var t=this;if(t._.cursors.length<1){return}if(t._.highlightRange){t.removeHighlight()}var s=t.toDomRange(),r=s.createBookmark();h.applyToRange(s);s.moveToBookmark(r);t._.highlightRange=s;var q=s.startContainer;if(q.type!=CKEDITOR.NODE_ELEMENT){q=q.getParent()}q.scrollIntoView();t.updateFromDomRange(s)},removeHighlight:function(){var q=this;if(!q._.highlightRange){return}var r=q._.highlightRange.createBookmark();h.removeFromRange(q._.highlightRange);q._.highlightRange.moveToBookmark(r);q.updateFromDomRange(q._.highlightRange);q._.highlightRange=null},isReadOnly:function(){if(!this._.highlightRange){return 0}return this._.highlightRange.startContainer.isReadOnly()},moveBack:function(){var q=this;var s=q._.walker.back(),r=q._.cursors;if(s.hitMatchBoundary){q._.cursors=r=[]}r.unshift(s);if(r.length>q._.rangeLength){r.pop()}return s},moveNext:function(){var q=this;var s=q._.walker.next(),r=q._.cursors;if(s.hitMatchBoundary){q._.cursors=r=[]}r.push(s);if(r.length>q._.rangeLength){r.shift()}return s},getEndCharacter:function(){var q=this._.cursors;if(q.length<1){return null}return q[q.length-1].character},getNextCharacterRange:function(t){var r,q,s=this._.cursors;if((r=s[s.length-1])&&r.textNode){q=new g(e(r))}else{q=this._.walker}return new f(q,t)},getCursors:function(){return this._.cursors}};function e(s,r){var q=new CKEDITOR.dom.range();q.setStart(s.textNode,r?s.offset:s.offset+1);q.setEndAt(z.document.getBody(),CKEDITOR.POSITION_BEFORE_END);return q}function d(r){var q=new CKEDITOR.dom.range();q.setStartAt(z.document.getBody(),CKEDITOR.POSITION_AFTER_START);q.setEnd(r.textNode,r.offset);return q}var c=0,b=1,a=2,F=function(t,r){var q=[-1];if(r){t=t.toLowerCase()}for(var s=0;s<t.length;s++){q.push(q[s]+1);while(q[s+1]>0&&t.charAt(s)!=t.charAt(q[s+1]-1)){q[s+1]=q[q[s+1]-1]+1}}this._={overlap:q,state:0,ignoreCase:!!r,pattern:t}};F.prototype={feedCharacter:function(r){var q=this;if(q._.ignoreCase){r=r.toLowerCase()}for(;;){if(r==q._.pattern.charAt(q._.state)){q._.state++;if(q._.state==q._.pattern.length){q._.state=0;return a}return b}else{if(!q._.state){return c}else{q._.state=q._.overlap[q._.state]}}}return null},reset:function(){this._.state=0}};var E=/[.,"'?!;: \u0085\u00a0\u1680\u280e\u2028\u2029\u202f\u205f\u3000]/,D=function(r){if(!r){return true}var q=r.charCodeAt(0);return q>=9&&q<=13||q>=8192&&q<=8202||E.test(r)},C={searchRange:null,matchRange:null,find:function(R,P,t,s,r,q){var u=this;if(!u.matchRange){u.matchRange=new f(new g(u.searchRange),R.length)}else{u.matchRange.removeHighlight();u.matchRange=u.matchRange.getNextCharacterRange(R.length)}var X=new F(R,!P),W=c,V="%";while(V!==null){u.matchRange.moveNext();while(V=u.matchRange.getEndCharacter()){W=X.feedCharacter(V);if(W==a){break}if(u.matchRange.moveNext().hitMatchBoundary){X.reset()}}if(W==a){if(t){var U=u.matchRange.getCursors(),T=U[U.length-1],S=U[0],Q=d(S),x=e(T);Q.trim();x.trim();var w=new g(Q,true),v=new g(x,true);if(!(D(w.back().character)&&D(v.next().character))){continue}}u.matchRange.setMatched();if(r!==false){u.matchRange.highlight()}return true}}u.matchRange.clearMatched();u.matchRange.removeHighlight();if(s&&!q){u.searchRange=B(1);u.matchRange=null;return arguments.callee.apply(u,Array.prototype.slice.call(arguments).concat([true]))}return false},replaceCounter:0,replace:function(v,u,t,s,r,q,N){var w=this;j=1;var M=0;if(w.matchRange&&w.matchRange.isMatched()&&!w.matchRange._.isReplaced&&!w.matchRange.isReadOnly()){w.matchRange.removeHighlight();var L=w.matchRange.toDomRange(),K=z.document.createText(t);if(!N){var x=z.getSelection();x.selectRanges([L]);z.fire("saveSnapshot")}L.deleteContents();L.insertNode(K);if(!N){x.selectRanges([L]);z.fire("saveSnapshot")}w.matchRange.updateFromDomRange(L);if(!N){w.matchRange.highlight()}w.matchRange._.isReplaced=true;w.replaceCounter++;M=1}else{M=w.find(u,s,r,q,!N)}j=0;return M}};function B(t){var r,q=z.getSelection(),s=z.document.getBody();if(q&&!t){r=q.getRanges()[0].clone();r.collapse(true)}else{r=new CKEDITOR.dom.range();r.setStartAt(s,CKEDITOR.POSITION_AFTER_START)}r.setEndAt(s,CKEDITOR.POSITION_BEFORE_END);return r}var A=z.lang.findAndReplace;return{title:A.title,resizable:CKEDITOR.DIALOG_RESIZE_NONE,minWidth:350,minHeight:170,buttons:[CKEDITOR.dialog.cancelButton],contents:[{id:"find",label:A.find,title:A.find,accessKey:"",elements:[{type:"hbox",widths:["230px","90px"],children:[{type:"text",id:"txtFindFind",label:A.findWhat,isChanged:false,labelLayout:"horizontal",accessKey:"F"},{type:"button",id:"btnFind",align:"left",style:"width:100%",label:A.find,onClick:function(){var q=this.getDialog();if(!C.find(q.getValueOf("find","txtFindFind"),q.getValueOf("find","txtFindCaseChk"),q.getValueOf("find","txtFindWordChk"),q.getValueOf("find","txtFindCyclic"))){alert(A.notFoundMsg)}}}]},{type:"fieldset",label:CKEDITOR.tools.htmlEncode(A.findOptions),style:"margin-top:29px",children:[{type:"vbox",padding:0,children:[{type:"checkbox",id:"txtFindCaseChk",isChanged:false,label:A.matchCase},{type:"checkbox",id:"txtFindWordChk",isChanged:false,label:A.matchWord},{type:"checkbox",id:"txtFindCyclic",isChanged:false,"default":true,label:A.matchCyclic}]}]}]},{id:"replace",label:A.replace,accessKey:"M",elements:[{type:"hbox",widths:["230px","90px"],children:[{type:"text",id:"txtFindReplace",label:A.findWhat,isChanged:false,labelLayout:"horizontal",accessKey:"F"},{type:"button",id:"btnFindReplace",align:"left",style:"width:100%",label:A.replace,onClick:function(){var q=this.getDialog();if(!C.replace(q,q.getValueOf("replace","txtFindReplace"),q.getValueOf("replace","txtReplace"),q.getValueOf("replace","txtReplaceCaseChk"),q.getValueOf("replace","txtReplaceWordChk"),q.getValueOf("replace","txtReplaceCyclic"))){alert(A.notFoundMsg)}}}]},{type:"hbox",widths:["230px","90px"],children:[{type:"text",id:"txtReplace",label:A.replaceWith,isChanged:false,labelLayout:"horizontal",accessKey:"R"},{type:"button",id:"btnReplaceAll",align:"left",style:"width:100%",label:A.replaceAll,isChanged:false,onClick:function(){var r=this.getDialog(),q;C.replaceCounter=0;C.searchRange=B(1);if(C.matchRange){C.matchRange.removeHighlight();C.matchRange=null}z.fire("saveSnapshot");while(C.replace(r,r.getValueOf("replace","txtFindReplace"),r.getValueOf("replace","txtReplace"),r.getValueOf("replace","txtReplaceCaseChk"),r.getValueOf("replace","txtReplaceWordChk"),false,true)){}if(C.replaceCounter){alert(A.replaceSuccessMsg.replace(/%1/,C.replaceCounter));z.fire("saveSnapshot")}else{alert(A.notFoundMsg)}}}]},{type:"fieldset",label:CKEDITOR.tools.htmlEncode(A.findOptions),children:[{type:"vbox",padding:0,children:[{type:"checkbox",id:"txtReplaceCaseChk",isChanged:false,label:A.matchCase},{type:"checkbox",id:"txtReplaceWordChk",isChanged:false,label:A.matchWord},{type:"checkbox",id:"txtReplaceCyclic",isChanged:false,"default":true,label:A.matchCyclic}]}]}]}],onLoad:function(){var t=this,r,q,s=0;this.on("hide",function(){s=0});this.on("show",function(){s=1});this.selectPage=CKEDITOR.tools.override(this.selectPage,function(u){return function(J){u.call(t,J);var I=t._.tabs[J],x,w,v;w=J==="find"?"txtFindFind":"txtFindReplace";v=J==="find"?"txtFindWordChk":"txtReplaceWordChk";r=t.getContentElement(J,w);q=t.getContentElement(J,v);if(!I.initialized){x=CKEDITOR.document.getById(r._.inputId);I.initialized=true}if(s){l.call(this,J)}}})},onShow:function(){var t=this;C.searchRange=B();var s=t.getParentEditor().getSelection().getSelectedText(),r=y=="find"?"txtFindFind":"txtFindReplace",q=t.getContentElement(y,r);q.setValue(s);q.select();t.selectPage(y);t[(y=="find"&&t._.editor.readOnly?"hide":"show")+"Page"]("replace")},onHide:function(){var q;if(C.matchRange&&C.matchRange.isMatched()){C.matchRange.removeHighlight();z.focus();q=C.matchRange.toDomRange();if(q){z.getSelection().selectRanges([q])}}delete C.matchRange},onFocus:function(){if(y=="replace"){return this.getContentElement("replace","txtFindReplace")}else{return this.getContentElement("find","txtFindFind")}}}};CKEDITOR.dialog.add("find",function(a){return k(a,"find")});CKEDITOR.dialog.add("replace",function(a){return k(a,"replace")})})();