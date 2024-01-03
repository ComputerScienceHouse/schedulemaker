<?php
////////////////////////////////////////////////////////////////////////////
// RATE MY PROFESSORS PROXY
//
// @author	Mary Strodl (mstrodl@csh.rit.edu)
//
// @file	js/rmp.php
// @descrip	Provides a proxy to the RMP site to avoid XSS
////////////////////////////////////////////////////////////////////////////
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
	"Authorization: Basic dGVzdDp0ZXN0", // Literally test:test in base64... YES this is required.
	"Content-Type: application/json",
]);
$body = json_decode(file_get_contents('php://input'), true);
$name = $body['name'];
$payload = json_encode([
  "query" => "query TeacherSearchResultsPageQuery(\n  \$query: TeacherSearchQuery!\n  \$schoolID: ID\n  \$includeSchoolFilter: Boolean!\n) {\n  search: newSearch {\n    ...TeacherSearchPagination_search_1ZLmLD\n  }\n  school: node(id: \$schoolID) @include(if: \$includeSchoolFilter) {\n    __typename\n    ... on School {\n      name\n    }\n    id\n  }\n}\n\nfragment TeacherSearchPagination_search_1ZLmLD on newSearch {\n  teachers(query: \$query, first: 8, after: \"\") {\n    didFallback\n    edges {\n      cursor\n      node {\n        ...TeacherCard_teacher\n        id\n        __typename\n      }\n    }\n    pageInfo {\n      hasNextPage\n      endCursor\n    }\n    resultCount\n    filters {\n      field\n      options {\n        value\n        id\n      }\n    }\n  }\n}\n\nfragment TeacherCard_teacher on Teacher {\n  id\n  legacyId\n  avgRating\n  numRatings\n  ...CardFeedback_teacher\n  ...CardSchool_teacher\n  ...CardName_teacher\n  ...TeacherBookmark_teacher\n}\n\nfragment CardFeedback_teacher on Teacher {\n  wouldTakeAgainPercent\n  avgDifficulty\n}\n\nfragment CardSchool_teacher on Teacher {\n  department\n  school {\n    name\n    id\n  }\n}\n\nfragment CardName_teacher on Teacher {\n  firstName\n  lastName\n}\n\nfragment TeacherBookmark_teacher on Teacher {\n  id\n  isSaved\n}\n",
  "variables" => [
    "query" => [
      "text" => $name,
      "schoolID" => "U2Nob29sLTgwNw==",
      "fallback" => false, // No results from other schools
      "departmentID" => null,
    ],
    "schoolID" => "U2Nob29sLTgwNw==",
    "includeSchoolFilter" => true
  ],
]);
curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

curl_setopt($curl, CURLOPT_URL, "https://www.ratemyprofessors.com/graphql");
echo curl_exec($curl);
