"use client";
import React from "react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Bell,
  CircleQuestionMark,
  LogOut,
  Settings,
  User2,
  UserPen,
} from "lucide-react";
import Link from "next/link";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import UserSettings from "../Home/Navbar/Settings";

const Navbar = () => {
  return (
    <div className=" p-3  flex justify-between items-center px-5 bg-[#87CEEB] shadow-lg">
      <div className=" invert">
        <Link
          href="/dashboard"
          title="Go to Dashboard"
          className="flex items-center gap-2"
        >
          <svg viewBox="0 0 100 100" className="w-12 h-12 text-black">
            <path
              d="M 10 10 L 90 10 L 90 90 L 10 90 Z"
              stroke="black"
              strokeWidth="10"
              fill="none"
            />
            <path
              d="M 35 75 L 35 25 L 65 75 L 65 25"
              stroke="black"
              strokeWidth="10"
              fill="none"
            />
          </svg>

          <div>
            <h1 className="font-bold text-2xl leading-5 tracking-[4px] ">
              FIN
            </h1>
            <h1 className="font-bold text-2xl leading-none">LAB</h1>
          </div>
        </Link>
      </div>

      <div className="flex gap-3 font-bold items-center ">
        <span
          className="cursor-pointer flex items-center gap-2 rounded-full bg-sky-50 p-2 
                   hover:bg-sky-50 hover:scale-105 transition-all duration-200"
        >
          <CircleQuestionMark /> Help
        </span>

        <span className="cursor-pointer p-2 rounded-full hover:bg-gray-200 hover:scale-110 transition-all duration-200">
          <Bell />
        </span>

        <span className="cursor-pointer p-2 rounded-full hover:bg-gray-200 hover:scale-110 transition-all duration-200">
          <DropdownMenu>
            <DropdownMenuTrigger className={"cursor-pointer"}>
              <User2 />
            </DropdownMenuTrigger>
            <DropdownMenuContent className={"mr-5 mt-5 w-56"}>
              <DropdownMenuLabel className={"bg-gray-100 "}>
                <h1 className="font-bold text-[16px]">Super Admin</h1>
                <span className="text-muted-foreground mt-2">
                  Super@admin.com
                </span>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />

              <Dialog>
                <DialogTrigger asChild>
                  <DropdownMenuItem
                    onSelect={(e) => e.preventDefault()}
                    className="flex items-center gap-2 cursor-pointer"
                  >
                    <UserPen /> View Profile
                  </DropdownMenuItem>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>Are you absolutely sure?</DialogTitle>
                    <DialogDescription>
                      This action cannot be undone. This will permanently delete
                      your account and remove your data from our servers.
                    </DialogDescription>
                  </DialogHeader>
                </DialogContent>
              </Dialog>
              <Dialog>
                <DialogTrigger asChild>
                  <DropdownMenuItem
                    onSelect={(e) => e.preventDefault()}
                    className="flex items-center gap-2 cursor-pointer"
                  >
                    <Settings /> Settings
                  </DropdownMenuItem>
                </DialogTrigger>
                <DialogContent className={"!max-w-[60vw] "}>
                  <DialogTitle className={"hidden"}></DialogTitle>
                  <UserSettings />
                </DialogContent>
              </Dialog>
              <Link href={"/"}>
                <DropdownMenuItem
                  className={"flex items-center cursor-pointer"}
                >
                  <LogOut /> Sign Out
                </DropdownMenuItem>
              </Link>
            </DropdownMenuContent>
          </DropdownMenu>
        </span>
      </div>
    </div>
  );
};

export default Navbar;
