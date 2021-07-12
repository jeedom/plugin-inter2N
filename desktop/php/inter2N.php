<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('inter2N');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
   <div class="col-xs-12 eqLogicThumbnailDisplay">
  <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
  <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction logoPrimary" data-action="add">
        <i class="fas fa-plus-circle"></i>
        <br>
        <span>{{Ajouter}}</span>
    </div>
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
      <i class="fas fa-wrench"></i>
    <br>
    <span>{{Configuration}}</span>
  </div>
  </div>
  <legend><i class="fas fa-table"></i> {{Mes equipements}}</legend>
	   <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
<div class="eqLogicThumbnailContainer">
    <?php
foreach ($eqLogics as $eqLogic) {
	$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
	echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
	
			$namesearch = $eqLogic->getConfiguration('modelName');
                      if (in_array($namesearch, array("2N Access Unit M", "2n Helios IP Vario", "2N IP Solo", "2N IP Verso"))){                         
                           echo '<img class="lazy" src="plugins/inter2N/core/config/devices/' .$eqLogic->getConfiguration('modelName'). '.jpeg"/>';
			} else {
			   echo '<img src="' . $plugin->getPathImgIcon() . '" />';
                    
			 }
	
	echo '<br>';
	echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
	echo '</div>';
}
?>
</div>
</div>

<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
    <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
    <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
  </ul>
  <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
    <div role="tabpanel" class="tab-pane active" id="eqlogictab">
      <br/>
    <form class="form-horizontal">
        <fieldset>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                <div class="col-sm-3">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                <div class="col-sm-3">
                    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                        <option value="">{{Aucun}}</option>
                        <?php
                        foreach (jeeObject::all() as $object) {
                            echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                        }
                        ?>
                   </select>
               </div>
           </div>
	   <div class="form-group">
                <label class="col-sm-3 control-label">{{Catégorie}}</label>
                <div class="col-sm-9">
                 <?php
                    foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                    echo '<label class="checkbox-inline">';
                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                    echo '</label>';
                    }
                  ?>
               </div>
           </div>
	<div class="form-group">
		<label class="col-sm-3 control-label"></label>
		<div class="col-sm-9">
			<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
			<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
		</div>
	</div>
    <div class="form-group">
		<label class="col-sm-3 control-label">IP</label>
		<div class="col-sm-3">
			<div class="input-group">
				<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="protocole" style="width:auto;">
					<option value='https'>HTTPS</option>
				</select>
				<span class="input-group-addon">://</span>
				<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ip" placeholder="IP"/>
				<span class="input-group-addon">:</span>
				<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="portconfig" value="443" placeholder="443"/>
			</div>
		</div>
	</div>
       <div class="form-group">
            <label class="col-sm-3 control-label">{{Nom d'utilisateur}}</label>
            <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="username" placeholder="nom d'utilisateur"/>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Mot de passe}}</label>
            <div class="col-sm-3">
                <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" placeholder="mot de passe"/>
            </div>
        </div>
         <div class="form-group">
            <label class="col-sm-3 control-label">{{Module Empreinte Digitale}}</label>
            <div class="col-sm-3">
			    <div class="input-group">
				  <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="fingerprintselect" style="width:auto;">
                       <option value='' ></option>
					   <option value='yes' > Oui </option>
					   <option value='no'> Non </option>
				  </select>
			    </div>
           </div> 
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Camera}}</label>
            <div class="col-sm-3">
			    <div class="input-group">
				  <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cameraselect" style="width:auto;">
                       <option value='' ></option>
					   <option value='yes' > Oui </option>
					   <option value='no'> Non </option>
				  </select>
			    </div>
           </div> 
        </div>
             <div class="form-group">
                 <label class="col-sm-3 control-label">{{Interrupteur de protection Anti-Ouverture}}</label>
                 <div class="col-sm-3">
			           <div class="input-group">
                          <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="tamperswitchprot" style="width:auto;">
                               <option value='' ></option>
                               <option value='yes' > Oui </option>
                               <option value='no'> Non </option>
                          </select>
			          </div>
                </div> 
             </div>

</fieldset>
</form>

<hr>

</div>


      <div role="tabpanel" class="tab-pane" id="commandtab">
         <a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
				<br/><br/>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th>{{Id}}</th>
								<th>{{Nom}}</th>
								<th>{{Type}}</th>
								<th>{{Paramètres}}</th>
								<th>{{Options}}</th>
								<th>{{Action}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
</div>
</div>

</div>
</div>

<?php include_file('desktop', 'inter2N', 'js', 'inter2N');?>
<?php include_file('core', 'plugin.template', 'js');?>
