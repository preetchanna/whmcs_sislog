<?php

namespace WHMCS\Module\Addon\Multipay\Admin;
use WHMCS\Database\Capsule;
/**
 * Sample Admin Area Controller
 */
class Controller {

    /**
     * Index action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function index($vars)
    {
        // Get common module parameters
        $modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
        $version = $vars['version']; // eg. 1.0
        $LANG = $vars['_lang']; // an array of the currently loaded language variables

        // Get module configuration parameters
        // $configTextField = $vars['Text Field Name'];

        $searchFields = [
            'invoiceid'     => 'Invoice ID',
            'reference'     => 'Reference',
            'entity'        => 'Entity',
            'transactionid' => 'Transaction ID',
        ];

        

        $limit = 10;
        $page = isset($_GET['page'])?$_GET['page']:1;
        $offset = $limit*($page-1);

        $query = Capsule::table('mod_gateway_multipay');
        $fieldValue = "";$fieldType= "";
        if(isset($_POST["fieldType"]) && isset($_POST["fieldValue"])){
            $fieldType  = trim($_POST["fieldType"]);
            $fieldValue = trim($_POST["fieldValue"]);
            $query->where($fieldType, $fieldValue);
        }
        $totalRecords = $query->count();
        if($page>1) $query->offset($offset);
            
        $data = $query->limit($limit)
            ->orderBy('id', 'desc')
            ->get();

        $tableBody = "";
        if(count($data)>0){
            foreach($data as $k => $d){
                $class = ($d->pay_status=='unpaid')?'danger':'success';
                $tableBody .= "<tr>";
                $tableBody .= "<td>{$d->id}</td>";
                $tableBody .= "<td>{$d->invoiceid}</td>";
                $tableBody .= "<td>{$d->reference}</td>";
                $tableBody .= "<td>{$d->entity}</td>";
                $tableBody .= "<td>{$d->amount}</td>";
                $tableBody .= "<td>{$d->transactionid}</td>";
                $tableBody .= "<td><span class='label label-{$class}'>{$d->pay_status}</span></td>";
                $tableBody .= "<td>{$d->deadline}</td>";
                $tableBody .= "</tr>";
            }
        } else {
            $tableBody .= "<tr><td colspan='100%'>No Records found!!</td></tr>";
        }
        
        //determine the total number of pages available  
        $number_of_page = ceil ($totalRecords / $limit); 
        $prev = $page - 1;
        $next = $page + 1;

        //display the link of the pages in URL  
        $paging = '';
        $paging = '<nav aria-label="...">
        <ul class="pagination">';
        if($page <= 1){
            $paging .= '<li class="page-item disabled">
                <span class="page-link">Previous</span>
            </li>';
        } else {
            $paging .= '<li class="page-item">
            <a class="page-link" href="'.$modulelink.'&page='.$prev.'">Previous</a>
            </li>';
        }

        if($page < $number_of_page){
            $paging .= '<li class="page-item">
            <a class="page-link" href="'.$modulelink.'&page='.$next.'">Next</a>
            </li>';
        } else {
            $paging .= '<li class="page-item disabled">
                <span class="page-link">Next</span>
            </li>';
        }
        $paging .='</ul></nav>';

        $searchSelectField = '<select class="form-control" name="fieldType">';
        foreach($searchFields as $k => $v){
            $searchSelectField .= '<option value="'.$k.'"';
            if($k == $fieldType)
                $searchSelectField .= ' selected';
            $searchSelectField .= '>'.$v.'</option>';
        }
        $searchSelectField .= '</select>';

        return <<<EOF

<h2>SISLog: Payment Gateway</h2>

<p>This is payment addon module to handle custom WHMCS `mod_gateway_multipay` table.</p>
<div class="panel panel-default">
  <div class="panel-body">
  <form class="form-inline" method="post" action="{$modulelink}">
    <div class="form-group mb-2">
    $searchSelectField
    </div>
  <div class="form-group mx-sm-3 mb-2">
    <label for="valueField" class="sr-only">Value</label>
    <input type="text" class="form-control" id="valueField" name="fieldValue" value="{$fieldValue}">
  </div>
  <button type="submit" class="btn btn-success mb-2 btn-sm">Search</button>
</form>
  </div>
</div>

<table class="table table-bordered">
  <thead>
    <tr>
      <th scope="col">#ID</th>
      <th scope="col">#Invoice</th>
      <th scope="col">Reference</th>
      <th scope="col">Entity</th>
      <th scope="col">Amount(MZN)</th>
      <th scope="col">Transaction</th>
      <th scope="col">Status</th>
      <th scope="col">Deadline</th>
    </tr>
  </thead>
  <tbody>$tableBody<tbody>
</table>

$paging

EOF;
    }

    /**
     * Show action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function show($vars)
    {
        // Get common module parameters
        $modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
        $version = $vars['version']; // eg. 1.0
        $LANG = $vars['_lang']; // an array of the currently loaded language variables

        // Get module configuration parameters
        $configTextField = $vars['Text Field Name'];
        $configPasswordField = $vars['Password Field Name'];
        $configCheckboxField = $vars['Checkbox Field Name'];
        $configDropdownField = $vars['Dropdown Field Name'];
        $configRadioField = $vars['Radio Field Name'];
        $configTextareaField = $vars['Textarea Field Name'];

        return <<<EOF

<h2>Show</h2>

<p>This is the <em>show</em> action output of the sample addon module.</p>

<p>The currently installed version is: <strong>{$version}</strong></p>

<p>
    <a href="{$modulelink}" class="btn btn-info">
        <i class="fa fa-arrow-left"></i>
        Back to home
    </a>
</p>

EOF;
    }
}
