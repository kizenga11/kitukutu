<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admission Application | Kitukutu Technical School</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

*{
box-sizing:border-box;
}

body{
font-family:Arial, sans-serif;
margin:0;
background:#f4f6f9;
color:#1e293b;
}

/* HEADER */

.header{
background:#0f2b4b;
color:white;
padding:15px;
display:flex;
align-items:center;
justify-content:space-between;
}

.header h1{
margin:0;
font-size:20px;
}

.back-btn{
background:#f4b400;
color:#0f2b4b;
padding:8px 15px;
border-radius:5px;
text-decoration:none;
font-weight:bold;
}

/* CONTAINER */

.container{
max-width:1100px;
margin:auto;
padding:20px;
}

/* GRID */

.grid{
display:grid;
grid-template-columns:1fr 2fr;
gap:20px;
}

/* CARD */

.card{
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 4px 10px rgba(0,0,0,0.05);
}

/* FORM */

input,select{
width:100%;
padding:12px;
margin-bottom:12px;
border-radius:6px;
border:1px solid #ccc;
}

button{
background:#1e4a6d;
color:white;
padding:12px;
border:none;
border-radius:6px;
width:100%;
font-size:16px;
cursor:pointer;
}

button:hover{
background:#f4b400;
color:black;
}

/* INSTRUCTIONS */

.instructions li{
margin-bottom:10px;
font-size:14px;
}

/* MOBILE */

@media(max-width:768px){

.grid{
grid-template-columns:1fr;
}

.header{
flex-direction:column;
gap:10px;
}

}

</style>

</head>

<body>

<!-- HEADER -->

<div class="header">

<h1>Admission Application Portal</h1>

<a href="index.php" class="back-btn">
<i class="fas fa-arrow-left"></i> Back
</a>

</div>


<div class="container">

<div class="grid">

<!-- LEFT SIDE -->

<div>

<div class="card">

<h3>Instructions</h3>

<ul class="instructions">

<li>Form One applicants must enter PSLE Index Number.</li>

<li>PSLE format example: PS0205004-0001/2025</li>

<li>Form Three applicants must enter Form Two Index Number.</li>

<li>Form Two format example: S1981/0001/2023</li>

<li>Form Two and Form Four applicants must upload signed results.</li>

<li>Results must include Head of School contact.</li>

<li>Save your Application Number after submitting.</li>

</ul>

</div>


<div class="card">

<h3>Track Application</h3>

<form action="track.php" method="GET">

<input type="text"
name="application_no"
placeholder="Enter Application Number"
required>

<button type="submit">
Track Status
</button>

</form>

</div>

</div>


<!-- RIGHT SIDE FORM -->

<div class="card">

<h3>Apply for Admission</h3>

<form action="submit_admission.php"
method="POST"
enctype="multipart/form-data">

<label>First Name</label>
<input type="text"
name="first_name"
required>

<label>Middle Name</label>
<input type="text"
name="middle_name"
required>

<label>Last Name</label>
<input type="text"
name="last_name"
required>

<label>Gender</label>

<select name="gender" required>

<option value="">Select Gender</option>
<option>Male</option>
<option>Female</option>

</select>

<label>Entry Level</label>

<select name="entry_level"
required
onchange="toggleFields(this.value)">

<option value="">Select Level</option>

<option value="Form One">Form One</option>

<option value="Form Two">Form Two</option>

<option value="Form Three">Form Three</option>

<option value="Form Four">Form Four</option>

</select>


<div id="psle_field" style="display:none;">

<label>PSLE Index Number</label>

<input type="text"
name="psle_index_no"
placeholder="PS0205004-0001/2025">

</div>


<div id="form2_field" style="display:none;">

<label>Form Two Index Number</label>

<input type="text"
name="form2_index_no"
placeholder="S1981/0001/2023">

</div>


<div id="doc_field" style="display:none;">

<label>Upload Results (PDF)</label>

<input type="file"
name="document">

</div>


<label>Phone</label>

<input type="text"
name="phone"
required>

<label>Email</label>

<input type="email"
name="email">

<button type="submit">
Submit Application
</button>

</form>

</div>

</div>

</div>


<script>

function toggleFields(level){

document.getElementById("psle_field").style.display="none";
document.getElementById("form2_field").style.display="none";
document.getElementById("doc_field").style.display="none";

if(level=="Form One"){
document.getElementById("psle_field").style.display="block";
}

if(level=="Form Two"){
document.getElementById("doc_field").style.display="block";
}

if(level=="Form Three"){
document.getElementById("form2_field").style.display="block";
}

if(level=="Form Four"){
document.getElementById("doc_field").style.display="block";
}

}


document.querySelector("input[name='psle_index_no']")
.addEventListener("input", function(e){

this.value = this.value.toUpperCase();

});



</script>

</body>
</html>
