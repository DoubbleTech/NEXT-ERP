"use client";
import React, { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";

const Delegation = () => {
  const [checked, setChecked] = useState(false);
  return (
    <div className="h-[50vh] overflow-y-scroll  px-6">
      <h1 className=" font-semibold text-xl mb-7">
        Delegate Your Responsibilities
      </h1>

      <div>
        <div>
          <Label className="mb-2 block">Delegate to User</Label>
          <Select>
            <SelectTrigger className="w-full">
              <SelectValue placeholder="Select a User" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Users</SelectLabel>
                <SelectItem value="apple">John</SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>
        <div className="flex items-center mt-5 gap-4">
          <div className="mt-5 flex-1">
            <Label className="mb-2 block">Start Date</Label>
            <Input className="w-full" type="date" />
          </div>
          <div className="mt-5 flex-1">
            <Label
              className={`mb-2 block ${checked ? "text-muted-foreground" : ""}`}
            >
              End Date
            </Label>
            <Input disabled={checked} className="w-full" type="date" />
          </div>
        </div>
        <div className="mt-8">
          <Label className="mb-2 block">Reason for Delegation</Label>
          <Textarea placeholder="Explain why are you delegating..." />
        </div>
        <div className="mt-7 flex items-center gap-2">
          <input
            onChange={(e) => {
              setChecked(e.target.checked);
            }}
            type="checkbox"
            id="permanent"
            className="w-4 h-4 rounded-md border-2 border-gray-400 
               checked:bg-blue-500 checked:border-blue-500 
               focus:ring-2 focus:ring-blue-300 cursor-pointer"
          />
          <label htmlFor="remember" className="font-medium text-base">
            Permanent Delegation
          </label>
        </div>
        <Button className="bg-[#7BC9EE] hover:bg-sky-600 my-10 cursor-pointer">
          Save Delegation
        </Button>
      </div>
    </div>
  );
};

export default Delegation;
