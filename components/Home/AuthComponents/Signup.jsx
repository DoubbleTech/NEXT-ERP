"use client";
import React, { useState } from "react";
import { ArrowRight,ArrowLeft, Eye, EyeOff } from "lucide-react";
import Link from "next/link";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";


const Signup = () => {
   const [signupInfo, setSignupInfo] = useState(false);
  const businessType = [
    "Sole Proprietorship",
    "Partnership",
    "LLC",
    "Corporation",
    "Nonprofit",
    "Other",
  ];

  const countries = [
    { code: "US", name: "United States" },
    { code: "UK", name: "United Kingdom" },
    { code: "CA", name: "Canada" },
    { code: "AU", name: "Australia" },
    { code: "PK", name: "Pakistan" },
    { code: "IN", name: "India" },
    { code: "DE", name: "Germany" },
    { code: "FR", name: "France" },
    { code: "JP", name: "Japan" },
    { code: "BR", name: "Brazil" },
  ];

  const [showPassword, setShowPassword] = useState(false);
  
  const [businessInfo, setbusinessInfo] = useState(false);
  console.log(businessInfo);

  return (
    <div>
      <div className="flex items-center justify-center gap-1">
        <div className="rounded-full h-10  flex justify-center items-center w-10 bg-[#7BC9EE] text-white font-bold">
          <button>1</button>
        </div>
        <div className=" h-1 flex justify-center items-center w-10 bg-[#7BC9EE] text-white font-bold"></div>
        <div className="rounded-full h-10  flex justify-center items-center w-10 bg-[#7BC9EE] text-white font-bold">
          <button>2</button>
        </div>
      </div>
       {businessInfo?  <>
      
        <>
          {/* BusinessName */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="email">Business Name</label>
          </div>
          <Input
            id="businessName"
            type="email"
            placeholder="Enter your last name"
            className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
          />

          {/* BusinessType */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="businessType">Business Type</label>
          </div>
          <Select>
            <SelectTrigger className="w-full cursor-pointer px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]">
              <SelectValue placeholder="Select your business type" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Business Type</SelectLabel>
                {businessType.map((e, i) => {
                  return (
                    <SelectItem className={"cursor-pointer"} key={i} value={e}>
                      {e}
                    </SelectItem>
                  );
                })}
              </SelectGroup>
            </SelectContent>
          </Select>
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="email">
              Business Registration Number (Optional)
            </label>
          </div>
          <Input
            id="businessNo"
            type="businessNo"
            placeholder="Enter registration number"
            className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
          />
          {/* business Country */}
          <div className="font-semibold mb-2 mt-5">
            <label htmlFor="businessType">Business Country</label>
          </div>
          <Select>
            <SelectTrigger className="w-full cursor-pointer px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]">
              <SelectValue placeholder="Select your business type" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Business Type</SelectLabel>
                {countries.map((e, i) => {
                  return (
                    <SelectItem
                      className={"cursor-pointer"}
                      key={i}
                      value={e.code}
                    >
                      {e.name}
                    </SelectItem>
                  );
                })}
              </SelectGroup>
            </SelectContent>
          </Select>
          <div className="mt-7 flex items-center gap-2">
            <input
              type="checkbox"
              id="remember"
              className="w-4 h-4 rounded-md border-2 border-gray-400 
               checked:bg-blue-500 checked:border-blue-500 
               focus:ring-2 focus:ring-blue-300 cursor-pointer"
            />
            <label htmlFor="remember" className="text-gray-700 text-sm">
              I agree to the{" "}
              <Link className="font-semibold hover:underline" href={"/"}>
                Terms of Services
              </Link>{" "}
              and{" "}
              <Link className="font-semibold hover:underline" href={"/"}>
                Privacy Policy
              </Link>
            </label>
          </div>

          <div className="my-8 flex items-center gap-5">
            <div>
              {" "}
              <Button
                onClick={() => setbusinessInfo(false)}
                className="cursor-pointer h-12 text-[15px] font-bold "
                variant={"outline"}
              >
                <ArrowLeft /> Back
              </Button>
            </div>
            <div className="flex-1">
              <Button className=" w-full bg-[#7BC9EE] hover:shadow-xl cursor-pointer h-12 text-[15px] font-bold hover:bg-sky-600">
                Complete Registration
              </Button>
            </div>
          </div>
        </>
      
    </>: (
        <>
         {/* First Name */}
        <div className="font-semibold mb-2 mt-5">
          <label htmlFor="firstName">First Name</label>
        </div>
        <Input
          id="firstName"
          type="text"
          placeholder="Enter your first name"
          className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
        />
        {/* LastName */}
        <div className="font-semibold mb-2 mt-5">
          <label htmlFor="email">Last Name</label>
        </div>
        <Input
          id="lastName"
          type="email"
          placeholder="Enter your last name"
          className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
        />
        {/* Email */}
        <div className="font-semibold mb-2 mt-5">
          <label htmlFor="email">Email Address</label>
        </div>
        <Input
          id="email"
          type="email"
          placeholder="Enter your email"
          className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]"
        />
        {/* Country */}
        <div className="font-semibold mb-2 mt-5">
          <label htmlFor="country">Country</label>
        </div>
        <Select>
          <SelectTrigger className="w-full cursor-pointer px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px]">
            <SelectValue placeholder="Select your country" />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              <SelectLabel>Country</SelectLabel>
              {countries.map((e, i) => {
                return (
                  <SelectItem
                    className={"cursor-pointer"}
                    key={i}
                    value={e.code}
                  >
                    {e.name}
                  </SelectItem>
                );
              })}
            </SelectGroup>
          </SelectContent>
        </Select>

        {/* Password */}
        <div className="font-semibold mb-2 mt-5">
          <label htmlFor="password">Password</label>
        </div>
        <div className="relative">
          <Input
            id="password"
            type={showPassword ? "text" : "password"}
            placeholder="Enter your password"
            className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px] pr-12"
          />
          <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
          >
            {showPassword ? (
              <EyeOff className="cursor-pointer" size={20} />
            ) : (
              <Eye className="cursor-pointer" size={20} />
            )}
          </button>
          {/* repeat password */}
        </div>
        <div className="font-semibold mb-2 mt-5">
          <label htmlFor="password">Password</label>
        </div>
        <Input
          id="password"
          type={showPassword ? "text" : "password"}
          placeholder="Enter your password"
          className="w-full h-12 px-4 rounded-lg border border-gray-300 bg-gray-100 placeholder:text-[15px] pr-12"
        />

        <div className="my-8 flex items-center gap-5">
          <div>
            {" "}
            <Button
              className="cursor-pointer h-12 text-[15px] font-bold "
              variant={"outline"}
            >
              Cancel
            </Button>
          </div>
          <div className="flex-1">
            <Button type="button" onClick={()=>setbusinessInfo(true)} className=" w-full bg-[#7BC9EE] hover:shadow-xl cursor-pointer h-12 text-[15px] font-bold hover:bg-sky-600">
              Continue <ArrowRight />
            </Button>
          </div>
        </div>
        </>
       )}
    </div>
  );
};

export default Signup;
