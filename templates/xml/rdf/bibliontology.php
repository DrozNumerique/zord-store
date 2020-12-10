<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:bibo="http://purl.org/ontology/bibo/"
         xmlns:dc="http://purl.org/dc/terms/"
         xmlns:foaf="http://xmlns.com/foaf/0.1/"
         xmlns:address="http://schemas.talis.com/2005/address/schema#">
  <bibo:Book rdf:about="<?php echo $models['about']; ?>">
    <dc:title><?php echo $models['title']; ?></dc:title>
<?php if (isset($models['abstract'])) { ?>
<?php   if (is_array($models['abstract'])) { ?>
<?php     foreach ($models['abstract'] as $lang => $description) { ?>
    <dc:abstract xml:lang="<?php echo $lang; ?>"><?php echo $description; ?></dc:abstract>
<?php     }?>
<?php   } else if (is_string($models['abstract'])) { ?>
    <dc:abstract><?php echo $models['abstract']; ?></dc:abstract>
<?php   }?>
<?php } ?>
<?php if (isset($models['publisher'])) { ?>
    <dc:publisher>
      <foaf:Organization>
<?php if (isset($models['locality'])) { ?>
        <address:localityName><?php echo $models['locality']; ?></address:localityName>
<?php   }?>
        <foaf:name><?php echo $models['publisher']; ?></foaf:name>
      </foaf:Organization>
    </dc:publisher>
<?php } ?>
<?php if (isset($models['date'])) { ?>
    <dc:date><?php echo $models['date']; ?></dc:date>
<?php } ?>
<?php if (isset($models['language'])) { ?>
    <dc:language><?php echo $models['language']; ?></dc:language>
<?php } ?>
<?php if (isset($models['isbn'])) { ?>
    <bibo:isbn13><?php echo $models['isbn13']; ?></bibo:isbn13>
<?php } ?>
<?php if (isset($models['uri'])) { ?>
    <bibo:uri><?php echo $models['uri']; ?></bibo:uri>
<?php } ?>
<?php if (isset($models['type'])) { ?>
    <dc:type><?php echo $models['type']; ?></dc:type>
<?php } ?>
<?php if (isset($models['rights'])) { ?>
    <dc:rights><?php echo $models['rights']; ?></dc:rights>
<?php } ?>
<?php if (isset($models['format'])) { ?>
    <dc:format><?php echo $models['format']; ?></dc:format>
<?php } ?>
<?php if (isset($models['serie'])) { ?>
    <dc:isPartOf>
      <bibo:Series>
        <dc:title><?php echo $models['serie']; ?></dc:title>
<?php if (isset($models['number'])) { ?>
        <bibo:number><?php echo $models['number']; ?></bibo:number>
<?php } ?>
      </bibo:Series>
    </dc:isPartOf>
<?php } ?>
<?php if (isset($models['pages'])) {?>
    <bibo:numPages rdf:datatype="http://www.w3.org/2001/XMLSchema#integer"><?php echo $models['pages']; ?></bibo:numPages>
<?php } ?>
<?php if (isset($models['creator'])) { ?>
<?php   foreach ($models['creator'] as $creator) { ?>
<?php     $name = explode(',',$creator); ?>
    <dc:creator>
      <foaf:Person>
        <foaf:surname><?php echo $name[0]; ?></foaf:surname>
<?php     if (count($name) > 0) { ?>
        <foaf:givenname><?php echo $name[1]; ?></foaf:givenname>
<?php     } ?>
      </foaf:Person>
    </dc:creator>
<?php   } ?>
<?php } ?>
<?php if (isset($models['editor'])) { ?>
<?php   foreach ($models['editor'] as $editor) { ?>
<?php     $name = explode(',',$editor); ?>
    <bibo:editor>
      <foaf:Person>
        <foaf:surname><?php echo $name[0]; ?></foaf:surname>
<?php     if (count($name) > 0) { ?>
        <foaf:givenname><?php echo $name[1]; ?></foaf:givenname>
<?php     } ?>
      </foaf:Person>
    </bibo:editor>
<?php   } ?>
<?php } ?>
  </bibo:Book>
</rdf:RDF>
