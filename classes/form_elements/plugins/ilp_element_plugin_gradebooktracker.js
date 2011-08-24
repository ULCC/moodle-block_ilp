M.gradebooktracker_construct_url = function( location, inparam, invalue ){
    var url = new String( location );
    var qstring = new String();
    var urlparts = url.split( '?' );
    if( urlparts.length > 1 ){
        qstring = urlparts[ 1 ];
    }
    qstringparts = qstring.split( '&' );
    var paramlist = new Array();
    for( i in qstringparts ){
        var keyvalue = new String( qstringparts[ i ] );
        var keyvalueparts = keyvalue.split( '=' );
        if( keyvalueparts[ 0 ] == inparam ){
        }
        else{            
            if( keyvalue.length > 1 ){
                paramlist[ keyvalueparts[ 0 ] ] = keyvalueparts[ 1 ];
            }
        }
        paramlist[ inparam ] = invalue;
    }
    outqargs = new Array();
    var qargcount = 0;
    for( j in paramlist ){
        outqargs[ qargcount ] = j + '=' + paramlist[ j ];
        qargcount++;
    }
    var outqstring = outqargs.join( "&" );
    return urlparts[ 0 ] + '?' + outqstring;
}
M.gradebook_tracker_construct_url_exclusive = function( clocation, argname, value ){
    var clocationparts = new String( clocation ).split( '?' );
    var baseurl = clocationparts[ 0 ];
    if( '' != value ){
        return baseurl + '?' + argname + '=' + value;
    }
    else{
        return baseurl;
    }
}
