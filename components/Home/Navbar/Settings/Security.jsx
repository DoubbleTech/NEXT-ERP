"use client";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import React, { useState } from "react";

const Security = () => {
  const [changePass, setChangePass] = useState(false);

  return (
    <div className="h-[50vh] overflow-y-scroll px-4"> 
      <h1 className="font-semibold text-xl mb-7">Account Security</h1>

      <div className="flex items-center gap-6">
        <div>
          <Label className="mb-2 block">Full Name</Label>
          <Input className="w-72 text-gray-500" value="Super Admin" readOnly />
        </div>
        <div>
          <Label className="mb-2 block">Email</Label>
          <Input className="w-72 text-gray-500" value="Super@Admin.com" readOnly />
        </div>
      </div>

      <div className="mt-8">
        <div>
          <Label className="mb-4 block">Change Password</Label>
          <Button
            onClick={() => setChangePass(!changePass)}
            className="bg-[#7BC9EE] hover:bg-sky-600 cursor-pointer"
          >
            {changePass ? "Hide Password Fields" : "Change Password"}
          </Button>
        </div>

        {changePass && (
          <div className="space-y-4 mt-6 border-2 border-gray-100 rounded-xl p-5">
            <div>
              <Label className="mb-2 block">Current Password</Label>
              <Input type="password" placeholder="Enter current password" />
            </div>
            <div className="mt-7">
              <Label className="mb-2 block">New Password</Label>
              <Input type="password" placeholder="Enter new password" />
            </div>
            <div  className="mt-7">
              <Label className="mb-2 block">Confirm New Password</Label>
              <Input type="password" placeholder="Confirm new password" />
            </div>
            <div  className="mt-7">
                <Button
            className="bg-[#7BC9EE] hover:bg-sky-600 cursor-pointer"
          >Save Password
          </Button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default Security;
