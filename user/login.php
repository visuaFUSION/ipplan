<?php

// IPplan
// Jan 8, 2026
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
//

// Do NOT clear the logout cookie here - let authenticate() handle it.
// If cookie is set, authenticate() will force re-prompt for credentials.
// If cookie is not set, authenticate() will check if user has valid credentials.

// Redirect to dashboard.php which requires authentication
Header("Location: ../dashboard.php");
exit();
?>
